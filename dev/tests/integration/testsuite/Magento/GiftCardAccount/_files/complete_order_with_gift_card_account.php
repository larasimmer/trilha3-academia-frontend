<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\TestFramework\Helper\Bootstrap;
use Magento\Sales\Model\Order;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\Data\ShipmentItemCreationInterface;
use Magento\Sales\Api\ShipOrderInterface;
use Magento\GiftCardAccount\Observer\RefundTest;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/GiftCardAccount/_files/invoice_with_gift_card_account.php');

$objectManager = Bootstrap::getObjectManager();

/** @var Order $order */
$order = $objectManager->get(Order::class);
$order->loadByIncrementId(RefundTest::ORDER_INCREMENT_ID);

$orderItems = $order->getItems();
/** @var OrderItemInterface $orderItem */
$orderItem = array_pop($orderItems);

/** @var ShipmentItemCreationInterface $shipmentItem */
$shipmentItem = $objectManager->get(ShipmentItemCreationInterface::class);
$shipmentItem->setOrderItemId($orderItem->getItemId());
$shipmentItem->setQty($orderItem->getQtyOrdered());
/** @var ShipOrderInterface $shipOrder */
$shipOrder = $objectManager->get(ShipOrderInterface::class);
$shipOrder->execute($order->getEntityId(), [$shipmentItem]);
