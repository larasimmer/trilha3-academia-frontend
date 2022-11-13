<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/Customer/_files/three_customers.php');
Resolver::getInstance()->requireDataFixture('Magento/Sales/_files/order_list.php');
Resolver::getInstance()->requireDataFixture('Magento/CustomerSegment/_files/segment.php');

$objectManager = Bootstrap::getObjectManager();
$orderRepository = $objectManager->create(OrderRepositoryInterface::class);
$orders = [
    '100000002' => [
        OrderInterface::CUSTOMER_ID => 1,
        OrderInterface::CREATED_AT => date('Y-m-d, H:i:s', strtotime('-1 day'))
    ],
    '100000004' => [
        OrderInterface::CUSTOMER_ID => 2,
        OrderInterface::CREATED_AT => date('Y-m-d, H:i:s', strtotime('-2 month'))
    ],
];
foreach ($orders as $orderId => $data) {
    /** @var Order $order */
    $order = $objectManager->create(Order::class)->loadByIncrementId($orderId);
    $order->setCustomerIsGuest(false)->addData($data);
    $orderRepository->save($order);
}
