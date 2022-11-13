<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Block\Adminhtml\Manage\Accordion;

/**
 * Checks compared items grid appearance
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class RviewedTest extends AbstractManageTest
{
    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->block = $this->layout->createBlock(Rviewed::class);
    }

    /**
     * @magentoDataFixture Magento/Reports/_files/recently_viewed_product_by_customer.php
     *
     * @return void
     */
    public function testGetItemsCollection(): void
    {
        $this->prepareRegistry('customer@example.com', 'default');
        $this->assertCollectionItem(['simple2'], $this->block->getItemsCollection());
    }

    /**
     * @magentoDataFixture Magento/Reports/_files/recently_viewed_disabled_product_by_customer.php
     *
     * @return void
     */
    public function testGetItemProductCollectionWithDisabledProduct(): void
    {
        $this->prepareRegistry('customer@example.com', 'default');
        $this->assertEmpty($this->block->getItemsCollection());
    }
}
