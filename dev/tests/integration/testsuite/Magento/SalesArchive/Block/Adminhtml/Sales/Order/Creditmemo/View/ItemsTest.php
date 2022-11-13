<?php
/***
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesArchive\Block\Adminhtml\Sales\Order\Creditmemo\View;

use Magento\Sales\Block\Adminhtml\Order\Creditmemo\View\Items;
use Magento\SalesArchive\Block\Adminhtml\Sales\Order\AbstractItemsTest;

/**
 * Class to test Creditmemo items block
 * @magentoAppArea adminhtml
 */
class ItemsTest extends AbstractItemsTest
{
    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->key = 'current_creditmemo';
        $this->block = $this->layout->createBlock(Items::class);
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/SalesArchive/_files/archived_order_with_invoice_shipment_creditmemo.php
     * @return void
     */
    public function testItemsAvailableOnPage(): void
    {
        $collection = $this->orderFactory->create()
            ->loadByIncrementId('100000111')
            ->getCreditmemosCollection();
        $this->assertCount(1, $collection);
        $this->registerItem($collection->getFirstItem());
        $this->assertCount(2, $this->block->getCreditmemo()->getAllItems());
    }
}
