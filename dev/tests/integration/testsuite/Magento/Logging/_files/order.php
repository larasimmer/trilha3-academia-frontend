<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\TestFramework\Helper\Bootstrap;
use Magento\Sales\Api\Data\ShipmentItemCreationInterface;
use Magento\Sales\Api\ShipOrderInterface;
use Magento\Sales\Model\Order\Item;

$addressData = [
    'region' => 'CA',
    'region_id' => '12',
    'postcode' => '11111',
    'lastname' => 'lastname',
    'firstname' => 'firstname',
    'street' => 'street',
    'city' => 'Los Angeles',
    'email' => 'admin1@example.com',
    'telephone' => '11111111',
    'country_id' => 'US'
];

$objectManager = Bootstrap::getObjectManager();

/** @var $product \Magento\Catalog\Model\Product */
$product = $objectManager->create(\Magento\Catalog\Model\Product::class);
$product
    ->setTypeId('simple')
    ->setId(1)
    ->setAttributeSetId(4)
    ->setWebsiteIds([1])
    ->setName('Simple Product')
    ->setSku('simple')
    ->setPrice(10)
    ->setMetaTitle('meta title')
    ->setMetaKeyword('meta keyword')
    ->setMetaDescription('meta description')
    ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
    ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
    ->setStockData(['use_config_manage_stock' => 1, 'qty' => 22, 'is_in_stock' => 1])
    ->setQty(22)
    ->save();


/** @var $billingAddress \Magento\Sales\Model\Order\Address */
$billingAddress = $objectManager->create(
    \Magento\Sales\Model\Order\Address::class,
    ['data' => $addressData]
);
$billingAddress->setAddressType('billing');

$shippingAddress = clone $billingAddress;
$shippingAddress->setId(null)->setAddressType('shipping');

/** @var $payment \Magento\Sales\Model\Order\Payment */
$payment = $objectManager->create(
    \Magento\Sales\Model\Order\Payment::class
);
$payment->setMethod('checkmo');

/** @var Item $orderItem */
$orderItem = $objectManager->create(Item::class);
$orderItem->setProductId($product->getId());
$orderItem->setName($product->getName());
$orderItem->setSku($product->getSku());
$orderItem->setQtyOrdered(1);
$orderItem->setQtyShipped(0);
$orderItem->setBasePrice($product->getPrice());
$orderItem->setPrice($product->getPrice());
$orderItem->setRowTotal($product->getPrice());
$orderItem->setProductType(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE);
$orderItem->setIsQtyDecimal(true);

/** @var $order \Magento\Sales\Model\Order */
$order = $objectManager->create(\Magento\Sales\Model\Order::class);
$order->addItem(
    $orderItem
)->setIncrementId(
    '100000001'
)->setSubtotal(
    100
)->setBaseSubtotal(
    100
)->setCustomerIsGuest(
    true
)->setCustomerEmail(
    'admin1@example.com'
)->setBillingAddress(
    $billingAddress
)->setShippingAddress(
    $shippingAddress
)->setStoreId(
    $objectManager->get(
        \Magento\Store\Model\StoreManagerInterface::class
    )->getStore()->getId()
)->setData(
    'shipping_method',
    'flatrate_flatrate'
)->setPayment(
    $payment
);
$order->save();

/** @var ShipmentItemCreationInterface $shipmentItem */
$shipmentItem = $objectManager->get(ShipmentItemCreationInterface::class);
$shipmentItem->setOrderItemId($orderItem->getItemId());
$shipmentItem->setQty($orderItem->getQtyOrdered());
/** @var ShipOrderInterface $shipOrder */
$shipOrder = $objectManager->get(ShipOrderInterface::class);
$shipOrder->execute($order->getEntityId(), [$shipmentItem]);
