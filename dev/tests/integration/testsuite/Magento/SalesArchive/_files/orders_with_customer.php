<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Sales\Model\Order;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\TestFramework\Helper\Bootstrap;

/** @var Bootstrap */
$objectManager = Bootstrap::getObjectManager();

/** @var Payment $payment */
$payment = $objectManager->create(Payment::class);
$payment->setMethod('checkmo')
    ->setAdditionalInformation('last_trans_id', '11122')
    ->setAdditionalInformation(
        'metadata',
        [
            'type' => 'free',
            'fraudulent' => false,
        ]
    );

$orders = [
    [
        'increment_id' => '100000007',
        'state' => Order::STATE_NEW,
        'status' => 'processing',
        'grand_total' => 110.00,
        'subtotal' => 110.00,
        'base_grand_total' => 110.00,
        'store_id' => 0,
        'website_id' => 0,
        'payment' => $payment
    ],
    [
        'increment_id' => '100000008',
        'state' => Order::STATE_NEW,
        'status' => 'processing',
        'grand_total' => 120.00,
        'subtotal' => 120.00,
        'base_grand_total' => 120.00,
        'store_id' => 1,
        'website_id' => 1,
        'payment' => $payment
    ],
    [
        'increment_id' => '100000009',
        'state' => Order::STATE_PROCESSING,
        'status' => 'processing',
        'grand_total' => 130.00,
        'base_grand_total' => 130.00,
        'subtotal' => 130.00,
        'total_paid' => 130.00,
        'store_id' => 0,
        'website_id' => 0,
        'payment' => $payment
    ],
    [
        'increment_id' => '100000010',
        'state' => Order::STATE_PROCESSING,
        'status' => 'closed',
        'grand_total' => 140.00,
        'base_grand_total' => 140.00,
        'subtotal' => 140.00,
        'store_id' => 1,
        'website_id' => 1,
        'payment' => $payment
    ],
    [
        'increment_id' => '100000011',
        'state' => Order::STATE_COMPLETE,
        'status' => 'complete',
        'grand_total' => 150.00,
        'base_grand_total' => 150.00,
        'subtotal' => 150.00,
        'total_paid' => 150.00,
        'store_id' => 1,
        'website_id' => 1,
        'payment' => $payment
    ],
    [
        'increment_id' => '1000000012',
        'state' => Order::STATE_COMPLETE,
        'status' => 'complete',
        'grand_total' => 160.00,
        'base_grand_total' => 160.00,
        'subtotal' => 160.00,
        'total_paid' => 160.00,
        'store_id' => 1,
        'website_id' => 1,
        'payment' => $payment
    ],
];

/** @var OrderRepositoryInterface $orderRepository */
$orderRepository = $objectManager->create(OrderRepositoryInterface::class);

/** @var array $orderData */
foreach ($orders as $orderData) {
    /** @var $order Order */
    $order = $objectManager->create(Order::class);

    $order->setData($orderData);
    $orderRepository->save($order);
}
