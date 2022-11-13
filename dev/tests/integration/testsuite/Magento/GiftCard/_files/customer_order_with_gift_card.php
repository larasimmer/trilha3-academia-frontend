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
use Magento\Customer\Model\CustomerRegistry;
use Magento\Sales\Model\Order\Address;
use Magento\Sales\Model\Order\AddressFactory;
use Magento\Sales\Model\Order\Payment;
use Magento\Store\Model\StoreManagerInterface;

Resolver::getInstance()->requireDataFixture('Magento/GiftCard/_files/gift_card_physical_with_fixed_amount_10.php');
Resolver::getInstance()->requireDataFixture('Magento/Customer/_files/customer.php');

$objectManager = Bootstrap::getObjectManager();

/** @var CustomerRegistry $customerRegistry */
$customerRegistry = $objectManager->create(CustomerRegistry::class);
$customer = $customerRegistry->retrieve(1);
/** @var AddressFactory $addressFactory */
$addressFactory = $objectManager->get(AddressFactory::class);
$billingAddress = $addressFactory->create(['data' => [
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
]]);
$billingAddress->setAddressType(Address::TYPE_BILLING);

/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->create(ProductRepositoryInterface::class);
$product = $productRepository->get('gift-card-with-fixed-amount-10');

$shippingAddress = clone $billingAddress;
$shippingAddress->setId(null)->setAddressType('shipping');

/** @var Payment $payment */
$payment =$objectManager->create(Payment::class);
$payment->setMethod('checkmo');
$requestInfo = ['options' => []];

/** @var $orderGiftCardItem Item */
$orderGiftCardItem = $objectManager->create(Item::class);
$orderGiftCardItem->setProductId($product->getId())
    ->setProductType(Giftcard::TYPE_GIFTCARD)
    ->setBasePrice(10)
    ->setPrice(10)
    ->setPriceInclTax(10)
    ->setBasePrice(10)
    ->setBasePriceInclTax(10)
    ->setOriginalPrice(10)
    ->setBaseOriginalPrice(10)
    ->setRowTotal(20)
    ->setBaseRowTotal(20)
    ->setQtyOrdered(2)
    ->setQtyOrdered(2)
    ->setIsQtyDecimal(0)
    ->setStoreId($objectManager->get(StoreManagerInterface::class)->getStore()->getId())
    ->setSku($product->getSku())
    ->setWeight(1)
    ->setName($product->getName())
    ->setIsVirtual(0)
    ->setProductOptions(
        [
            'info_buyRequest' => $requestInfo,
            'giftcard_amount' => 'custom',
            'custom_giftcard_amount' => 10,
            'giftcard_sender_name' => 'Gift Card Sender Name',
            'giftcard_sender_email' => 'sender@example.com',
            'giftcard_recipient_name' => 'Gift Card Recipient Name',
            'giftcard_recipient_email' => 'recipient@example.com',
            'giftcard_message' => 'Gift Card Message',
            'giftcard_email_template' => 'giftcard_email_template',
        ]
    );

/** @var $order Order */
$order = $objectManager->create(Order::class);
$order->addItem($orderGiftCardItem)
    ->setCustomerId($customer->getId())
    ->setCustomerIsGuest(false)
    ->setCustomerEmail($customer->getEmail())
    ->setIncrementId('100000001')
    ->setStoreId($objectManager->get(StoreManagerInterface::class)->getStore()->getId())
    ->setEmailSent(1)
    ->setBillingAddress($billingAddress)
    ->setShippingAddress($shippingAddress)
    ->setSubtotal(20)
    ->setBaseSubtotal(20)
    ->setBaseSubtotal(20)
    ->setBaseGrandTotal(20)
    ->setShippingAmount(10)
    ->setBaseShippingAmount(10)
    ->setTaxAmount(0)
    ->setBaseTaxAmount(0)
    ->setGrandTotal(30)
    ->setOrderCurrencyCode('USD')
    ->setBaseCurrencyCode('EUR')
    ->setGlobalCurrencyCode('EUR')
    ->setStoreCurrencyCode('USD')
    ->setTotalQtyOrdered(2)
    ->setState(Order::STATE_PROCESSING)
    ->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING))
    ->setPayment($payment);
$objectManager->get(OrderRepositoryInterface::class)
    ->save($order);

// creating the pool for gift-cards needed to generate the actual gifcard
$objectManager->get(MutableScopeConfigInterface::class)
    ->setValue(Pool::XML_CONFIG_POOL_SIZE, 2, 'website', 'base');
/** @var Pool $pool */
$pool = $objectManager->create(Pool::class);
$pool->setWebsiteId(1)
    ->generatePool();
