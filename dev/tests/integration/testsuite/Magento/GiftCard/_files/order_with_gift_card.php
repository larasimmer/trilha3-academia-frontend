<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard;
use Magento\GiftCardAccount\Model\Pool;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/Catalog/_files/product_simple.php');
$objectManager = Bootstrap::getObjectManager();
/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->create(ProductRepositoryInterface::class);
$product = $productRepository->get('simple');
/** @var $billingAddress \Magento\Sales\Model\Order\Address */
$billingAddress = Bootstrap::getObjectManager()->create(
    \Magento\Sales\Model\Order\Address::class,
    [
        'data' => [
            'firstname' => 'guest',
            'lastname' => 'guest',
            'email' => 'customer@example.com',
            'street' => 'street',
            'city' => 'Los Angeles',
            'region' => 'CA',
            'postcode' => '1',
            'country_id' => 'US',
            'telephone' => '1',
        ]
    ]
);
$billingAddress->setAddressType('billing');

$shippingAddress = clone $billingAddress;
$shippingAddress->setId(null)->setAddressType('shipping');

/** @var $payment \Magento\Sales\Model\Order\Payment */
$payment = Bootstrap::getObjectManager()->create(
    \Magento\Sales\Model\Order\Payment::class
);
$payment->setMethod('checkmo');

/** @var $orderGiftCardItem Item */
$orderGiftCardItem = Bootstrap::getObjectManager()->create(
    Item::class
);
$orderGiftCardItem->setProductId(1)
    ->setProductType(Giftcard::TYPE_GIFTCARD)
    ->setBasePrice(100)
    ->setQtyOrdered(2)
    ->setStoreId(1)
    ->setProductOptions(
        [
            'giftcard_amount' => 'custom',
            'custom_giftcard_amount' => 100,
            'giftcard_sender_name' => 'Gift Card Sender Name',
            'giftcard_sender_email' => 'sender@example.com',
            'giftcard_recipient_name' => 'Gift Card Recipient Name',
            'giftcard_recipient_email' => 'recipient@example.com',
            'giftcard_message' => 'Gift Card Message',
            'giftcard_email_template' => 'giftcard_email_template',
        ]
    );
/** @var Item $orderItemSimple */
$orderItemSimple = $objectManager->create(Item::class);
$orderItemSimple->setProductId($product->getId())
    ->setQtyOrdered(1)
    ->setBasePrice($product->getPrice())
    ->setPrice($product->getPrice())
    ->setRowTotal($product->getPrice())
    ->setProductType('simple');

/** @var $order Order */
$order = Bootstrap::getObjectManager()->create(Order::class);
$order->setCustomerEmail('mail@to.co')
    ->addItem($orderItemSimple)
    ->addItem($orderGiftCardItem)
    ->setCustomerEmail('someone@example.com')
    ->setIncrementId('100000001')
    ->setCustomerIsGuest(true)
    ->setStoreId(1)
    ->setEmailSent(1)
    ->setBillingAddress($billingAddress)
    ->setShippingAddress($shippingAddress)
    ->setPayment($payment);
Bootstrap::getObjectManager()->get(OrderRepositoryInterface::class)
    ->save($order);

Bootstrap::getObjectManager()->get(MutableScopeConfigInterface::class)
    ->setValue(Pool::XML_CONFIG_POOL_SIZE, 2, 'website', 'base');
/** @var $pool Pool */
$pool = Bootstrap::getObjectManager()->create(Pool::class);
$pool->setWebsiteId(1)
    ->generatePool();
