<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Block\Adminhtml\Manage\Accordion;

/**
 * Checks wish list items grid appearance
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation disabled
 * @magentoAppIsolation enabled
 */
class WishlistTest extends AbstractManageTest
{
    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->block = $this->layout->createBlock(Wishlist::class);
    }

    /**
     * @magentoDataFixture Magento/Wishlist/_files/wishlist.php
     *
     * @return void
     */
    public function testGetItemsCollection(): void
    {
        $this->markTestSkipped('Blocked by MC-36307');
        $this->prepareRegistry('customer@example.com', 'default');
        $collection = $this->block->getItemsCollection();
        $this->assertCount(1, $collection);
        $item = $collection->getFirstItem();
        $this->assertEquals('Simple Product', $item->getProductName());
    }

    /**
     * @magentoDataFixture Magento/Wishlist/_files/wishlist_with_disabled_product.php
     *
     * @return void
     */
    public function testGetItemsCollectionNotSalableProduct(): void
    {
        $this->prepareRegistry('customer@example.com', 'default');
        $this->assertEmpty($this->block->getItemsCollection());
    }

    /**
     * @magentoDataFixture Magento/Wishlist/_files/wishlist_with_not_visible_product.php
     *
     * @return void
     */
    public function testGetItemsCollectionNotVisibleProduct(): void
    {
        $this->prepareRegistry('customer@example.com', 'default');
        $this->assertEmpty($this->block->getItemsCollection());
    }
}
