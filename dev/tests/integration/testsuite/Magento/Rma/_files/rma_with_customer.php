<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Rma\Api\Data\ItemInterface;
use Magento\Rma\Model\Rma;
use Magento\Rma\Model\Rma\Source\Status;
use Magento\Rma\Model\Rma\Status\History;
use Magento\Rma\Model\Shipping;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\Sales\Model\Order;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Rma\Api\RmaRepositoryInterface;
use Magento\Rma\Api\TrackRepositoryInterface;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/Sales/_files/customer_order_with_two_items.php');

$objectManager = Bootstrap::getObjectManager();
/** @var Order $order */
$order = $objectManager->get(OrderInterfaceFactory::class)->create()->loadByIncrementId('100000555');
$orderItems = $order->getItems();
$orderItem = reset($orderItems);
/** @var $rma Rma */
$rma = $objectManager->create(Rma::class);
$rma->setOrderId($order->getId());
$rma->setIncrementId(1);
$rma->setStatus(Status::STATE_APPROVED);

$orderProduct = $orderItem->getProduct();
/** @var ItemInterface $rmaItem */
$rmaItem = $objectManager->create(ItemInterface::class);
$rmaItem->setData([
    'order_item_id'  => $orderItem->getId(),
    'product_name'   => $orderProduct->getName(),
    'product_sku'    => $orderProduct->getSku(),
    'qty_returned'   => 1,
    'is_qty_decimal' => 0,
    'qty_requested'  => 1,
    'qty_authorized' => 1,
    'qty_approved'   => 1,
    'status'         => Status::STATE_AUTHORIZED,
    'resolution' => 4,
    'condition' => 8,
    'reason' => 0,
    'reason_other' => 'don\'t like it'
]);
$rma->setItems([$rmaItem]);
/** @var RmaRepositoryInterface $rmaRepository */
$rmaRepository = $objectManager->get(RmaRepositoryInterface::class);
$rmaRepository->save($rma);

$history = $objectManager->create(History::class);
$history->setRma($rma);
$history->setRmaEntityId($rma->getId());
$history->saveComment('Test comment', true, true);

/** @var $trackingNumber Shipping */
$trackingNumber = $objectManager->create(Shipping::class);
$trackingNumber->setRmaEntityId($rma->getId())
    ->setCarrierTitle('CarrierTitle')
    ->setCarrierCode('custom')
    ->setTrackNumber('TrackNumber');
/** @var TrackRepositoryInterface $trackRepository */
$trackRepository = $objectManager->get(TrackRepositoryInterface::class);
$trackRepository->save($trackingNumber);
