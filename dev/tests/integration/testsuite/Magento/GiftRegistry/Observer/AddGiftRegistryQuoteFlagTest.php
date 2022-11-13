<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Observer;

use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Quote\Model\Quote\ItemFactory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Test for add gift registry flag to quote item.
 *
 * @see \Magento\GiftRegistry\Observer\AddGiftRegistryQuoteFlag
 * @magentoAppArea frontend
 * @magentoDbIsolation enabled
 */
class AddGiftRegistryQuoteFlagTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var ManagerInterface */
    private $eventManager;

    /** @var ProductInterfaceFactory */
    private $productFactory;

    /** @var ItemFactory */
    private $quoteItemFactory;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->eventManager = $this->objectManager->get(ManagerInterface::class);
        $this->productFactory = $this->objectManager->get(ProductInterfaceFactory::class);
        $this->quoteItemFactory = $this->objectManager->get(ItemFactory::class);
    }

    /**
     * @magentoConfigFixture current_store magento_giftregistry/general/enabled 1
     *
     * @return void
     */
    public function testAddFlagToQuoteItemWithEnabledGiftRegistry(): void
    {
        $product = $this->productFactory->create();
        $product->setGiftregistryItemId(989);
        $quoteItem = $this->quoteItemFactory->create();
        $this->eventManager->dispatch(
            'checkout_cart_product_add_after',
            ['quote_item' => $quoteItem, 'product' => $product]
        );
        $this->assertEquals($product->getGiftregistryItemId(), $quoteItem->getGiftregistryItemId());
    }

    /**
     * @magentoConfigFixture current_store magento_giftregistry/general/enabled 0
     *
     * @return void
     */
    public function testAddFlagToQuoteItemWithDisabledGiftRegistry(): void
    {
        $product = $this->productFactory->create();
        $product->setGiftregistryItemId(989);
        $quoteItem = $this->quoteItemFactory->create();
        $this->eventManager->dispatch(
            'checkout_cart_product_add_after',
            ['quote_item' => $quoteItem, 'product' => $product]
        );
        $this->assertFalse($quoteItem->hasGiftregistryItemId());
    }
}
