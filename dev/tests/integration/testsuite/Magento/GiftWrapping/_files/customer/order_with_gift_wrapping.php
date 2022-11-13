<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\TestFramework\Workaround\Override\Fixture\Resolver;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address as OrderAddress;
use Magento\GiftWrapping\Model\Wrapping as GiftWrapping;
use Magento\GiftWrapping\Model\ResourceModel\Wrapping as GiftWrappingResource;

Resolver::getInstance()->requireDataFixture('Magento/GiftWrapping/_files/wrappings.php');
Resolver::getInstance()->requireDataFixture('Magento/Catalog/_files/product_simple.php');
$addressData = include INTEGRATION_TESTS_DIR . '/testsuite/Magento/Sales/_files/address_data.php';


$objectManager = Bootstrap::getObjectManager();

/** @var Order $order */
/** @var Order\Payment $payment */
/** @var Order\Item $orderItem */
/** @var array $addressData Data for creating addresses for the orders. */
$orderData = [
        'increment_id' => '999999990',
        'state' => Order::STATE_NEW,
        'status' => 'processing',
        'grand_total' => 120.00,
        'subtotal' => 120.00,
        'base_grand_total' => 120.00,
        'store_id' => 1,
        'website_id' => 1,
];

/** @var OrderRepositoryInterface $orderRepository */
$orderRepository = $objectManager->create(OrderRepositoryInterface::class);

$payment = $objectManager->create(\Magento\Sales\Model\Order\Payment::class);
$payment->setMethod('checkmo');
$productRepository = $objectManager->create(ProductRepositoryInterface::class);

$product = $productRepository->get('simple');

/** @var  Magento\Sales\Model\Order $order */
$order = $objectManager->create(Order::class);

// Reset addresses
/** @var Order\Address $billingAddress */
$billingAddress = $objectManager->create(OrderAddress::class, ['data' => $addressData]);
$billingAddress->setAddressType('billing');

$shippingAddress = clone $billingAddress;
$shippingAddress->setId(null)->setAddressType('shipping');

/**
 * @var GiftWrappingResource $giftWrappingResource
 * @var GiftWrapping $giftWrappingForOrder
 */
$giftWrappingResource = $objectManager->create(GiftWrappingResource::class);
$giftWrappingForOrder = $objectManager->create(GiftWrapping::class);
$giftWrappingResource->load($giftWrappingForOrder, 'image1.png', 'image');
$giftWrappingForItem = $objectManager->create(GiftWrapping::class);
$giftWrappingResource->load($giftWrappingForItem, 'image2.png', 'image');

/** @var Order\Item $orderItem */
$orderItem = $objectManager->create(Order\Item::class);
$orderItem->setProductId($product->getId())
    ->setQtyOrdered(2)
    ->setBasePrice($product->getPrice())
    ->setPrice($product->getPrice())
    ->setRowTotal($product->getPrice())
    ->setProductType('simple')
    ->setGwId($giftWrappingForItem->getWrappingId());


$order
    ->setData($orderData)
    ->addItem($orderItem)
    ->setCustomerIsGuest(false)
    ->setCustomerId(1)
    ->setCustomerEmail('customer@example.com')
    ->setBillingAddress($billingAddress)
    ->setShippingAddress($shippingAddress)
    ->setPayment($payment);

$order->setGwId($giftWrappingForOrder->getWrappingId())
    ->setGwAllowGiftReceipt(1)
    ->setGwAddCard(1);
$orderRepository->save($order);
