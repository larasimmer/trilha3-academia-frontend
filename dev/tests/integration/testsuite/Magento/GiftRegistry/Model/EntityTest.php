<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Model;

use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Backend\App\Area\FrontNameResolver;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Mail\Template\TransportBuilderMock;
use PHPUnit\Framework\TestCase;

/**
 * Gift Registry model integration tests.
 */
class EntityTest extends TestCase
{
    /**
     * @var TransportBuilderMock
     */
    private $transportBuilder;

    /**
     * @var FrontNameResolver
     */
    private $frontNameResolver;

    /**
     * @var EntityFactory
     */
    private $entityFactory;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();

        $this->frontNameResolver = $objectManager->get(FrontNameResolver::class);
        $this->transportBuilder = $objectManager->get(TransportBuilderMock::class);
        $this->entityFactory = $objectManager->get(EntityFactory::class);
        $objectManager->get(StoreRepositoryInterface::class)->clean();
    }

    /**
     * Send Share Registry email using Admin scope.
     *
     * @magentoAppArea adminhtml
     * @return void
     */
    public function testAdminScopeSendShareRegistryEmail(): void
    {
        $this->sendEmailAndVerifyLocationLink();
    }

    /**
     * Send Share Registry email using Frontend scope.
     *
     * @magentoAppArea frontend
     * @return void
     */
    public function testFrontendScopeSendShareRegistryEmail(): void
    {
        $this->sendEmailAndVerifyLocationLink();
    }

    /**
     * Send Share Registry email and verify Registry Location link.
     *
     * @return void
     */
    private function sendEmailAndVerifyLocationLink(): void
    {
        $urlKey = 'gift_registry_url';
        $giftRegistry = $this->entityFactory->create();
        $giftRegistry->setUrlKey($urlKey);

        $this->assertTrue(
            $giftRegistry->sendShareRegistryEmail('example@mail.com', 1, 'message')
        );

        $backendFrontName = $this->frontNameResolver->getFrontName();
        $this->assertMatchesRegularExpression(
            "|(?<!{$backendFrontName})/giftregistry/view/index/id/{$urlKey}|s",
            $this->transportBuilder->getSentMessage()->getBody()->getParts()[0]->getRawContent()
        );
    }
}
