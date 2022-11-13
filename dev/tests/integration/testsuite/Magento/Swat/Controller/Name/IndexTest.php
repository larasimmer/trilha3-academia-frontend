<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Swat\Controller\Name;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\UrlCoder;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractController;

/**
 * Test class for \Magento\Swat\Controller\Name\Index
 * @magentoAppArea frontend
 * @magentoAppIsolation enabled
 */
class IndexTest extends AbstractController
{
    /**
     * Url to dispatch.
     */
    private const URI = 'swat/name';

    /**
     * @magentoDataFixture Magento/Swat/_files/create_jwts.php
     */
    public function testValidJwt()
    {
        $expectedResponse = [
            'status' => 'success',
            'admin_name' => \Magento\TestFramework\Bootstrap::ADMIN_FIRSTNAME .
                ' ' . \Magento\TestFramework\Bootstrap::ADMIN_LASTNAME
        ];
        $this->executeJwtTest('valid_jwt', $expectedResponse);
    }

    /**
     * @magentoDataFixture Magento/Swat/_files/create_jwts.php
     */
    public function testExpiredJwt()
    {
        $this->executeJwtTest('expired_jwt', Index::ERROR_RESPONSE);
    }

    /**
     * @param string $jwtName
     * @param array $expectedResponse
     */
    public function executeJwtTest(string $jwtName, array $expectedResponse)
    {
        $objectManager = Bootstrap::getObjectManager();
        $scopeConfig = $objectManager->get(ScopeConfigInterface::class);
        $jwt = $scopeConfig->getValue($jwtName);
        // encode + and / characters
        $jwtParam = $objectManager->get(UrlCoder::class)->encode($jwt);
        $this->dispatch(self::URI . '?jwt=' . $jwtParam);
        $jsonBody = $this->getResponse()->getBody();
        $response = $objectManager->get(Json::class)->unserialize($jsonBody);
        $this->assertEquals($expectedResponse, $response);
    }

    /**
     * @param string $params
     * @dataProvider errorPathDataProvider
     */
    public function testErrorPaths(string $params)
    {
        $this->dispatch(self::URI . $params);
        $jsonBody = $this->getResponse()->getBody();
        $response = Bootstrap::getObjectManager()->get(Json::class)->unserialize($jsonBody);
        $this->assertEquals(Index::ERROR_RESPONSE, $response);
    }

    /**
     * @return array
     */
    public function errorPathDataProvider(): array
    {
        return [
            [
                'no params' => ''
            ],
            [
                'empty jwt' => '?jwt='
            ],
            [
                'bad jwt' => '?jwt=12345'
            ]
        ];
    }
}
