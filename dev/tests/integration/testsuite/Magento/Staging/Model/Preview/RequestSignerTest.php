<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Staging\Model\Preview;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Staging\Model\VersionManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RequestSignerTest extends TestCase
{
    /**
     * @var RequestSigner
     */
    private $requestSigner;

    /**
     * @var DateTime
     */
    private $dateTime;

    const FAKE_TIMESTAMP = 1234567890;

    /**
     * @var DeploymentConfig|MockObject
     */
    private $deploymentConfig;

    /**
     * @var EncryptorInterface|MockObject
     */
    private $encryptor;

    protected function setUp(): void
    {
        $this->dateTime = $this->createMock(DateTime::class);
        $this->deploymentConfig = $this->createMock(DeploymentConfig::class);
        $this->deploymentConfig->method('get')
            ->willReturnMap([[ConfigOptionsListConstants::CONFIG_PATH_CRYPT_KEY, null, 'abc123']]);

        $this->encryptor = ObjectManager::getInstance()->create(
            EncryptorInterface::class,
            [
                'deploymentConfig' => $this->deploymentConfig
            ]
        );
        $this->requestSigner = ObjectManager::getInstance()->create(
            RequestSigner::class,
            [
                'dateTime' => $this->dateTime,
                'encryptor' => $this->encryptor,
            ]
        );
    }

    public function testBasicUsage()
    {
        $this->dateTime->method('timestamp')
            ->willReturn(self::FAKE_TIMESTAMP);

        $signed = $this->requestSigner->signUrl('http://test.local/foo/bar?' . VersionManager::PARAM_NAME . '=123');
        $isValid = $this->requestSigner->validateUrl($signed);

        self::assertTrue($isValid);
    }

    /**
     * @param string $url
     * @param string $expected
     * @dataProvider urlsToSignProvider
     */
    public function testSignUrl(string $url, string $expected)
    {
        $this->dateTime->method('timestamp')
            ->willReturn(self::FAKE_TIMESTAMP);

        $signed = $this->requestSigner->signUrl($url);

        self::assertSame($expected, $signed);
    }

    public function urlsToSignProvider()
    {
        $baseUrl = 'http://test.local/foo/bar?';
        $version = VersionManager::PARAM_NAME . '=123456';
        $signature = '&__signature=287ff44b9eb62be4cff081ab26e7282e31f49b33af3ef232cd3351cf31ae9248';
        $timestamp = '&__timestamp=' . self::FAKE_TIMESTAMP;

        return [
            'basic sign' => [
                $baseUrl . $version,
                $baseUrl . $version . $timestamp . $signature
            ],
            'extra params' => [
                $baseUrl . 'otherparam=abc&' . $version,
                $baseUrl . 'otherparam=abc&' . $version . $timestamp . $signature
            ],
            'extra params 2' => [
                $baseUrl . 'op1=abc&' . $version . '&op2=abc',
                $baseUrl . 'op1=abc&' . $version . '&op2=abc' . $timestamp . $signature
            ],
        ];
    }

    /**
     * @param string $url
     * @param bool $expected
     * @dataProvider validationResultsProvider
     */
    public function testValidateUrl(string $url, bool $expected)
    {
        $this->dateTime->method('timestamp')
            ->willReturn(self::FAKE_TIMESTAMP);

        $signed = $this->requestSigner->validateUrl($url);

        self::assertSame($expected, $signed);
    }

    /**
     * Tests signUrl() with invalid URL.
     */
    public function testExceptionForInvalidUrl()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'URL does not contain required preview version param'
        );

        $this->requestSigner->signUrl('http://test.local/foo/bar?foo=bar');
    }

    public function validationResultsProvider()
    {
        $baseUrl = 'http://test.local/foo/bar?';
        $version = VersionManager::PARAM_NAME . '=123456';
        $signature = '&__signature=287ff44b9eb62be4cff081ab26e7282e31f49b33af3ef232cd3351cf31ae9248';
        $timestamp = '&__timestamp=' . self::FAKE_TIMESTAMP;

        return [
            'basic valid' => [
                $baseUrl . $version . $timestamp . $signature,
                true
            ],
            'basic valid with extra params' => [
                $baseUrl . 'otherparam=abc&' . $version . $timestamp . $signature,
                true
            ],
            'basic valid with extra params 2' => [
                $baseUrl . 'op1=abc&' . $version . '&op2=abc' . $timestamp . $signature,
                true
            ],
            'invalid signature' => [
                $baseUrl . $version . $timestamp . '&__signature=abc',
                false,
            ],
            'invalid timestamp' => [
                $baseUrl . $version . '&__timestamp=bad' . $signature,
                false,
            ],
            'invalid version' => [
                $baseUrl . VersionManager::PARAM_NAME . '=invalid' . $timestamp . $signature,
                false
            ],
            'missing version' => [
                $baseUrl . $timestamp . $signature,
                false
            ],
            'missing timestamp' => [
                $baseUrl . $version . $signature,
                false
            ],
            'missing signature' => [
                $baseUrl . $version . $timestamp,
                false
            ],
            'no query' => [
                'http://test.local/foo/bar',
                false
            ],
        ];
    }

    public function testExpiredTimestamp()
    {
        $this->dateTime->method('timestamp')
            ->willReturn(self::FAKE_TIMESTAMP + 3601);

        $url = 'http://test.local/foo/bar?'
            . VersionManager::PARAM_NAME . '=123456'
            . '&__timestamp=' . self::FAKE_TIMESTAMP
            . '&__signature=287ff44b9eb62be4cff081ab26e7282e31f49b33af3ef232cd3351cf31ae9248';

        $result = $this->requestSigner->validateUrl($url);

        self::assertFalse($result);
    }

    public function testGenerateParamsWithDefaultTimestamp()
    {
        $this->dateTime->method('timestamp')
            ->willReturn(self::FAKE_TIMESTAMP);

        $params = $this->requestSigner->generateSignatureParams('123456');

        self::assertSame(
            [
                '__timestamp' => self::FAKE_TIMESTAMP,
                '__signature' => '287ff44b9eb62be4cff081ab26e7282e31f49b33af3ef232cd3351cf31ae9248',
            ],
            $params->getData()
        );
    }

    public function testGenerateParamsWithProvidedTimestamp()
    {
        $this->dateTime->method('timestamp')
            ->willReturn(self::FAKE_TIMESTAMP);

        $params = $this->requestSigner->generateSignatureParams('123456', '12345');

        self::assertSame(
            [
                '__timestamp' => '12345',
                '__signature' => '2eb681b25065dcf00e976c4e9dbb567278bddb8fe6d0bd541fb28321f94a0345',
            ],
            $params->getData()
        );
    }
}
