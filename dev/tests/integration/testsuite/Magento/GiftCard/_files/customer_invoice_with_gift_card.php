<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;
use Magento\Sales\Api\InvoiceManagementInterface;
use Magento\TestFramework\ObjectManager;
use Magento\Sales\Model\Order\Payment;
use Magento\Framework\DB\Transaction;
use Magento\Sales\Model\Order;

Resolver::getInstance()->requireDataFixture('Magento/GiftCard/_files/customer_order_with_gift_card.php');

/** @var ObjectManager; $objectManager */
$objectManager = Bootstrap::getObjectManager();

/** @var Order $order */
$order = $objectManager->get(OrderInterfaceFactory::class)->create()->loadByIncrementId('100000001');

$payment = $objectManager->create(Payment::class);
$payment->setMethod('checkmo');
$order->setPayment($payment);
$order->save();

/** @var InvoiceManagementInterface $orderService */
$orderService = $objectManager->create(InvoiceManagementInterface::class);

$invoice = $orderService->prepareInvoice($order);
$invoice->register();
$order = $invoice->getOrder();
$order->setIsInProcess(true);
$transactionSave = $objectManager
    ->create(Transaction::class);
$transactionSave->addObject($invoice)->addObject($order)->save();
