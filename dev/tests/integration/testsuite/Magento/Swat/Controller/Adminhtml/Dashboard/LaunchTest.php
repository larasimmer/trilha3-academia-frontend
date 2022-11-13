<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Swat\Controller\Adminhtml\Dashboard;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractBackendController;
use phpseclib3\Crypt\RSA;

/**
 * Test class for \Magento\Swat\Controller\Dashboard\Launch
 *
 * @magentoAppArea adminhtml
 */
class LaunchTest extends AbstractBackendController
{

    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /** @var WriterInterface  */
    private $configWriter;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->scopeConfig = $this->objectManager->get(ScopeConfigInterface::class);
        $this->configWriter = $this->objectManager->get(WriterInterface::class);

        $this->resource = Launch::ADMIN_RESOURCE;
        $this->uri = 'backend/swat/dashboard/launch';
        parent::setUp();
    }

    public function testExecuteWithoutKey()
    {
        $this->dispatch($this->uri);
        $this->assertRedirect($this->stringContains('?jwt='));
        $this->scopeConfig->clean();
        $this->assertTrue($this->scopeConfig->isSetFlag(Launch::CONFIG_RSA_PAIR_PATH));
    }

    public function testExecuteWithKey()
    {
        // create and store key pair
        $rsa = RSA::createKey();
        $keyPair = ['privatekey' => $rsa->__toString(), 'publickey' => $rsa->getPublicKey()->__toString()];
        $keyPairJson = $this->objectManager->get(Json::class)->serialize($keyPair);
        $encryptedKeyPair = $this->objectManager->get(Encryptor::class)->encrypt($keyPairJson);
        $this->configWriter->save(Launch::CONFIG_RSA_PAIR_PATH, $encryptedKeyPair);
        $scopeConfig = $this->objectManager->get(ScopeConfigInterface::class);
        $scopeConfig->clean();

        $this->dispatch($this->uri);
        $this->assertRedirect($this->stringContains('?jwt='));

        // get JWT
        $redirectUrl = '';
        foreach ($this->getResponse()->getHeaders() as $header) {
            if ($header->getFieldName() == 'Location') {
                $redirectUrl = $header->getFieldValue();
                break;
            }
        }
        $matches = [];
        preg_match('/jwt=(.*)/', $redirectUrl, $matches);
        $jwt = $matches[1];
        // encode + and / characters
        $jwt = base64_decode(strtr($jwt, '-_', '+/') . str_repeat('=', 3 - (3 + strlen($jwt)) % 4));
        list($base64UrlHeader, $base64UrlPayload, $base64UrlSignature) = explode('.', $jwt);
        $passedSignature = base64_decode($base64UrlSignature);

        // verify signature
        openssl_sign(
            $base64UrlHeader . '.' . $base64UrlPayload,
            $verifySignature,
            $keyPair['privatekey'],
            OPENSSL_ALGO_SHA256
        );
        $this->assertEquals($passedSignature, $verifySignature);
    }
}
