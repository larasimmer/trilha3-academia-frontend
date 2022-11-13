<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\GiftCardAccount\Api\Data\GiftCardAccountInterface;
use Magento\GiftCardAccount\Api\GiftCardAccountRepositoryInterface;
use Magento\GiftCardAccount\Helper\Data;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\Order\Payment;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;
use Magento\Customer\Model\CustomerRegistry;

Resolver::getInstance()->requireDataFixture('Magento/Sales/_files/default_rollback.php');
Resolver::getInstance()->requireDataFixture('Magento/Catalog/_files/product_simple.php');
Resolver::getInstance()->requireDataFixture('Magento/Customer/_files/customer.php');

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

$objectManager = Bootstrap::getObjectManager();
/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->create(ProductRepositoryInterface::class);
$product = $productRepository->get('simple');
$billingAddress = $objectManager->create(Address::class, ['data' => $addressData]);
$billingAddress->setAddressType('billing');

$shippingAddress = clone $billingAddress;
$shippingAddress->setId(null)
    ->setAddressType('shipping');

$payment = $objectManager->create(Payment::class);
$payment->setMethod('checkmo');

/** @var CustomerRegistry $customerRegistry */
$customerRegistry = $objectManager->create(CustomerRegistry::class);
$customer = $customerRegistry->retrieve(1);

/** @var Item $orderItem */
$orderItem = $objectManager->create(Item::class);
$orderItem->setProductId($product->getId())
    ->setQtyOrdered(2);
$orderItem->setBasePrice($product->getPrice());
$orderItem->setPrice($product->getPrice());
$orderItem->setRowTotal($product->getPrice());
$orderItem->setProductType('simple');

$storeId = $objectManager->get(StoreManagerInterface::class)
    ->getStore()
    ->getId();
/** @var Order $order */
$order = $objectManager->create(Order::class);
$order->setIncrementId('100000001')
    ->setState(Order::STATE_PROCESSING)
    ->setStatus(Order::STATE_PROCESSING)
    ->setSubtotal(100)
    ->setGrandTotal(100)
    ->setBaseSubtotal(100)
    ->setBaseGrandTotal(100)
    ->setBillingAddress($billingAddress)
    ->setShippingAddress($shippingAddress)
    ->setStoreId($storeId)
    ->addItem($orderItem)
    ->setCustomerId($customer->getId())
    ->setCustomerIsGuest(false)
    ->setCustomerEmail($customer->getEmail())
    ->setPayment($payment);

/** @var GiftCardAccountRepositoryInterface $giftCardRepository */
$giftCardRepository = $objectManager->get(GiftCardAccountRepositoryInterface::class);

/** @var GiftCardAccountInterface $giftCard1 */
$giftCard1 = $objectManager->create(GiftCardAccountInterface::class);
$giftCard1->setBaseGiftCardsAmount(10)
    ->setGiftCardsAmount(10)
    ->setBalance(10)
    ->setCode('TESTCODE1')
    ->setStatus(1);
$giftCardRepository->save($giftCard1);

/** @var GiftCardAccountInterface $giftCard2 */
$giftCard2 = $objectManager->create(GiftCardAccountInterface::class);
$giftCard2->setBaseGiftCardsAmount(15)
    ->setGiftCardsAmount(15)
    ->setBalance(15)
    ->setCode('TESTCODE2')
    ->setStatus(1);
$giftCardRepository->save($giftCard2);

$giftCards = [
    [
        "i" => $giftCard1->getGiftcardaccountId(),
        "c" => $giftCard1->getCode(),
        "a" => $giftCard1->getGiftCardsAmount(),
        "ba" => $giftCard1->getBaseGiftCardsAmount(),
    ],
    [
        "i" => $giftCard2->getGiftcardaccountId(),
        "c" => $giftCard2->getCode(),
        "a" => $giftCard2->getGiftCardsAmount(),
        "ba" => $giftCard2->getBaseGiftCardsAmount(),
    ],
];
$objectManager->create(Data::class)
    ->setCards($order, $giftCards);

$order->setBaseGiftCardsAmount(20);
$order->setGiftCardsAmount(20);
$order->setBaseGiftCardsInvoiced(10);
$order->setGiftCardsInvoiced(10);
$order->setBaseGiftCardsRefunded(5);
$order->setGiftCardsRefunded(5);
$orderRepository = $objectManager->get(OrderRepositoryInterface::class);
$orderRepository->save($order);
