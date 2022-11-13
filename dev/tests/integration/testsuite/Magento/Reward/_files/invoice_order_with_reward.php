<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Framework\DB\Transaction;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\Sales\Api\InvoiceManagementInterface;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/Sales/_files/order.php');

$objectManager =  ObjectManager::getInstance();
/** @var \Magento\Sales\Model\Order $order */
$order = $objectManager->get(OrderInterfaceFactory::class)->create()->loadByIncrementId('100000001');
$orderItems = $order->getItems();
$orderItem = reset($orderItems);
$orderItem
    ->setBaseRowTotal(100)
    ->setRowTotal(100)
    ->setQtyOrdered(10);

$order
    ->setBaseGrandTotal(90)
    ->setGrandTotal(90)
    ->setRewardPointsBalance(1000)
    ->setRewardCurrencyAmount(10)
    ->setBaseRewardCurrencyAmount(10);

/** * @var InvoiceManagementInterface $orderService */
$orderService = $objectManager->create(InvoiceManagementInterface::class);
$invoice = $orderService->prepareInvoice($order);
$invoice->register();
$order = $invoice->getOrder();
$order->setIsInProcess(true);
$transactionSave = $objectManager->create(Transaction::class);
$transactionSave->addObject($invoice)->addObject($order)->save();
