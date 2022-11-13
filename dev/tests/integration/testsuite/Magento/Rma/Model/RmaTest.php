<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Model;

use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Rma\Model\Rma\Source\Status;
use Magento\Sales\Model\Order;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class RmaTest extends TestCase
{
    /**
     * Test saveRma functionality
     *
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoDataFixture Magento/Rma/_files/order.php
     */
    public function testSaveRma()
    {
        $order = Bootstrap::getObjectManager()->create(Order::class);
        $order->loadByIncrementId('100000001');
        $rma = Bootstrap::getObjectManager()->create(Rma::class);
        $rmaItems = [];

        foreach ($order->getItemsCollection() as $item) {
            $rmaItems[] = [
                'order_item_id' => $item->getId(),
                'qty_requested' => '1',
                'resolution' => '3',
                'condition' => '6',
                'reason' => '10'
            ];
        }
        $data = [
            'customer_custom_email' => '',
            'items' => $rmaItems,
            'rma_comment' => 'comment'
        ];
        $rmaData = [
            'status' => Status::STATE_PENDING,
            'date_requested' => Bootstrap::getObjectManager()
                ->get(DateTime::class)
                ->gmtDate(),
            'order_id' => $order->getId(),
            'order_increment_id' => $order->getIncrementId(),
            'store_id' => $order->getStoreId(),
            'customer_id' => $order->getCustomerId(),
            'order_date' => $order->getCreatedAt(),
            'customer_name' => $order->getCustomerName(),
            'customer_custom_email' => 'example@domain.com',
        ];

        $rma->setData($rmaData)->saveRma($data);
        $rmaId = $rma->getId();

        unset($rma);
        $rma = Bootstrap::getObjectManager()->create(Rma::class);
        $rma->load($rmaId);
        $this->assertEquals($rma->getId(), $rmaId);
        $this->assertEquals($rma->getOrderId(), $order->getId());
        $this->assertEquals($rma->getCustomerCustomEmail(), $rmaData['customer_custom_email']);
        $this->assertEquals($rma->getOrderIncrementId(), $order->getIncrementId());
        $this->assertEquals($rma->getStoreId(), $order->getStoreId());
        $this->assertEquals($rma->getStatus(), Status::STATE_PENDING);
    }

    /**
     * Test saveRma with split return
     *
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoDataFixture Magento/Rma/_files/order_split.php
     */
    public function testSaveRmaWithSplitReturn()
    {
        $order = Bootstrap::getObjectManager()->create(Order::class);
        $order->loadByIncrementId('100000001');
        $rma = Bootstrap::getObjectManager()->create(Rma::class);
        $rmaItems = [];

        foreach ($order->getItemsCollection() as $item) {
            $rmaItems[] = [
                'order_item_id' => $item->getId(),
                'qty_requested' => 1,
                'qty_authorized' => 1,
                'resolution' => 3,
                'condition' => 6,
                'reason' => 10
            ];
        }
        $rmaItems[0]['entity_id'] = '10';
        $rmaItems[1]['entity_id'] = '10_123456789';
        $expectedData = [
            'customer_custom_email' => 'example@domain.com',
            'items' => $rmaItems,
            'rma_comment' => 'comment'
        ];
        $rmaData = [
            'status' => Status::STATE_PENDING,
            'date_requested' => Bootstrap::getObjectManager()
                ->get(DateTime::class)
                ->gmtDate(),
            'order_id' => $order->getId(),
            'order_increment_id' => $order->getIncrementId(),
            'store_id' => $order->getStoreId(),
            'customer_id' => $order->getCustomerId(),
            'order_date' => $order->getCreatedAt(),
            'customer_name' => $order->getCustomerName(),
            'customer_custom_email' => 'example@domain.com',
        ];

        $rma->setData($rmaData)->saveRma($expectedData);
        $items = $expectedData['items'];
        foreach ($rma->getItems() as $key => $rmaItem) {
            $expectedItem = $items[$key];
            $this->assertEquals($expectedItem['resolution'], $rmaItem->getResolution());
            $this->assertEquals($expectedItem['reason'], $rmaItem->getReason());
            $this->assertEquals($expectedItem['condition'], $rmaItem->getCondition());
            $this->assertEquals((float) $expectedItem['qty_requested'], $rmaItem->getQtyRequested());
            $this->assertEquals((float) $expectedItem['qty_authorized'], $rmaItem->getQtyAuthorized());
        }
    }
}
