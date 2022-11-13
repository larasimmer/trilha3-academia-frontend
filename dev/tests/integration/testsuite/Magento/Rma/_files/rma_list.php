<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
use Magento\Rma\Api\RmaRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/Sales/_files/order_list.php');

$objectManager = Bootstrap::getObjectManager();
/** @var RmaRepositoryInterface $rmaRepository */
$rmaRepository = $objectManager->get(RmaRepositoryInterface::class);
$orders = [
    ['increment_id' => '100000002'],
    ['increment_id' => '100000003'],
    ['increment_id' => '100000004'],
];
foreach ($orders as $orderData) {
    $order = $objectManager->create(\Magento\Sales\Model\Order::class);
    $order->load($orderData['increment_id'], 'increment_id');

    /** @var $rma \Magento\Rma\Model\Rma */
    $rma = $objectManager->create(\Magento\Rma\Model\Rma::class);
    $rma->setOrderId($order->getId());
    $rma->setIncrementId($orderData['increment_id']);
    $rmaRepository->save($rma);
}
