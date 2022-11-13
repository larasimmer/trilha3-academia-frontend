<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Block\Adminhtml\Manage\Accordion;

/**
 * Checks viewed products grid appearance for second website
 *
 * @magentoAppArea adminhtml
 */
class RviewedSecondWebsiteTest extends AbstractManageTest
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
     * @magentoDbIsolation disabled
     * @magentoDataFixture Magento/Reports/_files/recently_viewed_product_by_customer_second_website.php
     *
     * @return void
     */
    public function testGetItemProductCollectionFromSecondWebsite(): void
    {
        $this->prepareRegistry('customer@example.com', 'fixture_second_store');
        $this->assertCollectionItem(['simple-on-two-websites'], $this->block->getItemsCollection());
    }
}
