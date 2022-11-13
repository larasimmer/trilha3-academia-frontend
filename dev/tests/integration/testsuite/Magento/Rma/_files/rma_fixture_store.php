<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Rma\Api\Data\ItemInterface;
use Magento\Rma\Model\Rma;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Rma\Api\RmaRepositoryInterface;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/Sales/_files/order_fixture_store.php');

$objectManager = Bootstrap::getObjectManager();
/** @var \Magento\Sales\Model\Order $order */
$order = $objectManager->get(OrderInterfaceFactory::class)->create()->loadByIncrementId('100000004');
$orderItems = $order->getItems();
$orderItem = reset($orderItems);
/** @var $rma Rma */
$rma = $objectManager->create(Rma::class);
$rma->setOrderId($order->getId());
$rma->setStoreId($order->getStoreId());
$rma->setIncrementId(2);
$rma->setStatus('pending');

$orderProduct = $orderItem->getProduct();
/** @var ItemInterface $rmaItem */
$rmaItem = $objectManager->create(ItemInterface::class);
$rmaItem->setData(
    [
        'order_item_id'  => $orderItem->getId(),
        'product_name'   => $orderProduct->getName(),
        'product_sku'    => $orderProduct->getSku(),
        'qty_returned'   => 1,
        'is_qty_decimal' => 0,
        'qty_requested'  => 1,
        'qty_authorized' => 1,
        'qty_approved'   => 1,
        'status'         => $order->getStatus(),
    ]
);
$rma->setItems([$rmaItem]);
/** @var RmaRepositoryInterface $rmaRepository */
$rmaRepository = $objectManager->get(RmaRepositoryInterface::class);
$rmaRepository->save($rma);
