<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Block\Adminhtml\Manage\Accordion;

/**
 * Checks compared items grid appearance for second website
 *
 * @magentoAppArea adminhtml
 */
class RcomparedSecondWebsiteTest extends AbstractManageTest
{
    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->block = $this->layout->createBlock(Rcompared::class);
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture Magento/Reports/_files/recently_compared_product_second_website.php
     *
     * @return void
     */
    public function testGetItemProductCollectionFromSecondWebsite(): void
    {
        $this->prepareRegistry('customer@example.com', 'fixture_second_store');
        $this->assertCollectionItem(['simple-on-two-websites'], $this->block->getItemsCollection());
    }
}
