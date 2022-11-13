<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/Sales/_files/order_list.php');

$objectManager = Bootstrap::getObjectManager();

/** @var Collection $orderCollection */
$orderCollection = $objectManager->create(Collection::class);
/** @var OrderRepositoryInterface $orderRepository */
$orderRepository = $objectManager->get(OrderRepositoryInterface::class);
$orderList = $orderCollection->addFieldToFilter(
    'increment_id',
    ['in' => ['100000002','100000003','100000004']]
)->getItems();
/** @var array $orderList */
foreach ($orderList as $order) {
    $order
        ->setRewardPointsBalance(100)
        ->setRewardCurrencyAmount(15.1)
        ->setBaseRewardCurrencyAmount(14.9);

    $orderRepository->save($order);
}
