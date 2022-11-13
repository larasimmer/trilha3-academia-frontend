<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\DB\Transaction;
use Magento\Sales\Api\InvoiceManagementInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\Order\Payment;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/GiftWrapping/_files/quote_with_giftwrapping.php');
Resolver::getInstance()->requireDataFixture('Magento/Catalog/_files/product_simple.php');

$addressData = include INTEGRATION_TESTS_DIR . '/testsuite/Magento/Sales/_files/address_data.php';
/** @var Product $product */
$objectManager = Bootstrap::getObjectManager();
/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);
$product = $productRepository->get('simple');

$billingAddress = $objectManager->create(Address::class, ['data' => $addressData]);
$billingAddress->setAddressType('billing');
$shippingAddress = clone $billingAddress;
$shippingAddress->setId(null)->setAddressType('shipping');
$payment = $objectManager->create(Payment::class);
$payment->setMethod('checkmo');
/** @var Item $orderItem */
$orderItem = $objectManager->create(Item::class);
$orderItem->setProductId($product->getId())->setQtyOrdered(2);
$orderItem->setBasePrice($product->getPrice());
$orderItem->setPrice($product->getPrice());
$orderItem->setRowTotal($product->getPrice());
$orderItem->setProductType('simple');
$orderItem->setGwId(10);
$orderItem->setGwBasePrice(10);
$orderItem->setGwPrice(10);
$orderItem->setGwBaseTaxAmount(10);
$orderItem->setGwTaxAmount(10);
$orderItem->setGwBasePriceInvoiced(10);
$orderItem->setGwPriceInvoiced(10);
$orderItem->setGwBaseTaxAmountInvoiced(10);
$orderItem->setGwTaxAmountInvoiced(10);
/** @var Order $order */
$order = $objectManager->create(Order::class);
$order->setIncrementId('100000001')->setState(Order::STATE_PROCESSING)->setStatus(
    $order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING)
)->setSubtotal(100)->setGrandTotal(100)->setBaseSubtotal(100)->setBaseGrandTotal(100)->setCustomerIsGuest(
    true
)->setCustomerEmail('customer@null.com')->setBillingAddress($billingAddress)->setShippingAddress(
    $shippingAddress
)->setStoreId($objectManager->get(StoreManagerInterface::class)->getStore()->getId())->addItem(
    $orderItem
)->setPayment($payment);
$order->setGwId(10);
$order->setGwAllowGiftReceipt(10);
$order->setGwAddCard(10);
$order->setGwBasePrice(10);
$order->setGwPrice(10);
$order->setGwItemsBasePrice(10);
$order->setGwItemsPrice(10);
$order->setGwCardBasePrice(10);
$order->setGwCardPrice(10);
$order->setGwBaseTaxAmount(10);
$order->setGwTaxAmount(10);
$order->setGwItemsBaseTaxAmount(10);
$order->setGwItemsTaxAmount(10);
$order->setGwCardBaseTaxAmount(10);
$order->setGwCardTaxAmount(10);
$order->setGwBasePriceInclTax(10);
$order->setGwPriceInclTax(10);

$order->setGwItemsBasePriceInclTax(10);
$order->setGwItemsPriceInclTax(10);
$order->setGwCardBasePriceInclTax(10);
$order->setGwCardPriceInclTax(10);
$order->setGwBasePriceInvoiced(10);
$order->setGwPriceInvoiced(10);
$order->setGwItemsBasePriceInvoiced(10);
$order->setGwItemsPriceInvoiced(10);
$order->setGwCardBasePriceInvoiced(10);
$order->setGwCardPriceInvoiced(10);
$order->setGwBaseTaxAmountInvoiced(10);
$order->setGwTaxAmountInvoiced(10);
$order->setGwItemsBaseTaxInvoiced(10);
$order->setGwItemsTaxInvoiced(10);
$order->setGwCardBaseTaxInvoiced(10);
$order->setGwCardTaxInvoiced(10);
$order->save();

$orderService = ObjectManager::getInstance()->create(
    InvoiceManagementInterface::class
);
$invoice = $orderService->prepareInvoice($order);
$invoice->setIncrementId('i100000001');
$invoice->setGwBasePrice(10);
$invoice->setGwPrice(10);
$invoice->setGwItemsBasePrice(10);
$invoice->setGwItemsPrice(10);
$invoice->setGwCardBasePrice(10);
$invoice->setGwCardPrice(10);
$invoice->setGwBaseTaxAmount(10);
$invoice->setGwTaxAmount(10);
$invoice->setGwItemsBaseTaxAmount(10);
$invoice->setGwItemsTaxAmount(10);
$invoice->setGwCardBaseTaxAmount(10);
$invoice->setGwCardTaxAmount(10);
$invoice->register();
$order = $invoice->getOrder();
$order->setIsInProcess(
    true
);
$transactionSave = Bootstrap::getObjectManager()->create(
    Transaction::class
);
$transactionSave->addObject($invoice)->addObject($order)->save();
