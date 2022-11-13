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
class ComparedTest extends AbstractManageTest
{
    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->block = $this->layout->createBlock(Compared::class);
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/product_in_compare_list_with_customer.php
     *
     * @return void
     */
    public function testGetItemsCollection(): void
    {
        $this->prepareRegistry('customer@example.com', 'default');
        $this->assertCollectionItem(['simple2'], $this->block->getItemsCollection());
    }
}
