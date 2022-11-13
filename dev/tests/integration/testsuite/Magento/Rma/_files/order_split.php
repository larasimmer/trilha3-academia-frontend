<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Catalog\Model\Product\Type;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\Order\Payment;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;

$addressData = [
    'region' => 'CA',
    'region_id' => '12',
    'postcode' => '11111',
    'lastname' => 'lastname',
    'firstname' => 'firstname',
    'street' => 'street',
    'city' => 'Los Angeles',
    'email' => 'admin@example.com',
    'telephone' => '11111111',
    'country_id' => 'US'
];
/** @var $billingAddress Address */
$billingAddress = Bootstrap::getObjectManager()->create(
    Address::class,
    ['data' => $addressData]
);
$billingAddress->setAddressType('billing');

$shippingAddress = clone $billingAddress;
$shippingAddress->setId(null)->setAddressType('shipping');

/** @var $payment Payment */
$payment = Bootstrap::getObjectManager()->create(
    Payment::class
);
$payment->setMethod('checkmo');

/** @var $firstOrderItem Item */
$firstOrderItem = Bootstrap::getObjectManager()->create(
    Item::class
);
$firstOrderItem->setProductId(1)
    ->setProductType(Type::TYPE_SIMPLE)
    ->setName('product name')
    ->setSku('smp00001')
    ->setBasePrice(100)
    ->setQtyOrdered(1)
    ->setQtyShipped(1)
    ->setIsQtyDecimal(true);
/** @var $secondOrderItem Item */
$secondOrderItem = Bootstrap::getObjectManager()->create(
    Item::class
);
$secondOrderItem->setProductId(1)
    ->setProductType(Type::TYPE_SIMPLE)
    ->setName('product name')
    ->setSku('smp00001')
    ->setBasePrice(100)
    ->setQtyOrdered(1)
    ->setQtyShipped(1)
    ->setIsQtyDecimal(true);

/** @var $order Order */
$order = Bootstrap::getObjectManager()->create(Order::class);
$orderIncrementId = '100000001';
$order->addItem($firstOrderItem)
    ->addItem($secondOrderItem)
    ->setIncrementId($orderIncrementId)
    ->setSubtotal(100)
    ->setBaseSubtotal(100)
    ->setCustomerIsGuest(true)
    ->setCustomerEmail('admin@example.com')
    ->setBillingAddress($billingAddress)
    ->setShippingAddress($shippingAddress)
    ->setStoreId(
        Bootstrap::getObjectManager()
            ->get(StoreManagerInterface::class)
            ->getStore()
            ->getId()
    )
    ->setPayment($payment);
$order->save();
