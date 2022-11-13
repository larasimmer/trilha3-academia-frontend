<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Swat\Controller\Key;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Swat\Model\SwatKeyPair;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractController;
use phpseclib3\Crypt\RSA;

/**
 * Test class for \Magento\Swat\Controller\Key\Index
 * @magentoDbIsolation disabled
 */
class IndexTest extends AbstractController
{

    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var Json */
    private $json;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->json = $this->objectManager->get(Json::class);
        parent::setUp();
    }

    public function testExecuteWithoutJwksWithoutKey()
    {
        $this->dispatch('swat/key');
        $jsonBody = $this->getResponse()->getBody();
        $response = $this->json->unserialize($jsonBody);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('publicKey', $response);
        /** @var ScopeConfigInterface $scopeConfig */
        $scopeConfig = $this->objectManager->get(ScopeConfigInterface::class);
        $scopeConfig->clean();
        $this->assertNotEmpty($scopeConfig->getValue(SwatKeyPair::CONFIG_RSA_PAIR_PATH));
    }

    public function testExecuteWithJwks()
    {
        // create and store key pair
        $privateKey = RSA::createKey();
        $pubKey = $privateKey->getPublicKey()->toString($privateKey->getLoadedFormat());
        $privateKey = $privateKey->toString($privateKey->getLoadedFormat());

        $jwksJson = $this->objectManager->get(Json::class)->serialize([
            'privatekey' => $privateKey,
            'publickey' => $pubKey,
            'partialkey' => false,
        ]);
        $configWriter = $this->objectManager->get(WriterInterface::class);
        $configWriter->save(
            SwatKeyPair::CONFIG_RSA_PAIR_PATH,
            $this->objectManager->get(Encryptor::class)->encrypt($jwksJson)
        );
        $scopeConfig = $this->objectManager->get(ScopeConfigInterface::class);
        $scopeConfig->clean();

        $this->dispatch('swat/key');
        $jsonBody = $this->getResponse()->getBody();
        $response = $this->json->unserialize($jsonBody);
        $this->assertArrayHasKey('publicKey', $response);
        $this->assertEquals($pubKey, $response['publicKey']);
    }
}
