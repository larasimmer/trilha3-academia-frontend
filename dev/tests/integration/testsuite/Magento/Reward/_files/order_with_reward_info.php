<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/Sales/_files/order.php');

$objectManager = Bootstrap::getObjectManager();
/** @var OrderRepositoryInterface $orderRepository */
$orderRepository = $objectManager->create(OrderRepositoryInterface::class);
/** @var OrderInterface $order */
$order = $objectManager->create(OrderInterface::class)->load('100000001', 'increment_id');

$order->setData('reward_points_balance', 100)
    ->setData('reward_currency_amount', 15.1)
    ->setData('base_reward_currency_amount', 14.9);

$orderRepository->save($order);
