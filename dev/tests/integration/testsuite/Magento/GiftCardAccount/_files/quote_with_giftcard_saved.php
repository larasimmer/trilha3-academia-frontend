<?php
/**
 * Save quote_with_giftcard_saved fixture
 *
 * The quote is not saved inside the original fixture. It is later saved inside child fixtures, but along with some
 * additional data which may break some tests.
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

/** @var $model \Magento\GiftCardAccount\Model\Giftcardaccount */
$model = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
    \Magento\GiftCardAccount\Model\Giftcardaccount::class
);
$model->setCode(
    $giftCardCode ?? 'giftcardaccount_fixture'
)->setStatus(
    \Magento\GiftCardAccount\Model\Giftcardaccount::STATUS_ENABLED
)->setState(
    \Magento\GiftCardAccount\Model\Giftcardaccount::STATE_AVAILABLE
)->setWebsiteId(
    \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(
        \Magento\Store\Model\StoreManagerInterface::class
    )->getWebsite()->getId()
)->setIsRedeemable(
    \Magento\GiftCardAccount\Model\Giftcardaccount::REDEEMABLE
)->setBalance(
    9.99
)->setDateExpires(
    date('Y-m-d', strtotime('+1 week'))
)->save();

Resolver::getInstance()->requireDataFixture('Magento/Customer/_files/customer.php');
Resolver::getInstance()->requireDataFixture('Magento/Customer/_files/customer_address.php');
Resolver::getInstance()->requireDataFixture('Magento/Catalog/_files/products.php');

$addressData = include __DIR__ . '/../../../Magento/Sales/_files/address_data.php';

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

/** @var \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository */
$customerRepository = $objectManager->create(\Magento\Customer\Api\CustomerRepositoryInterface::class);
$customer = $customerRepository->getById(1);
/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->create(ProductRepositoryInterface::class);
$product = $productRepository->get('simple');
/** @var \Magento\Customer\Api\AddressRepositoryInterface $addressRepository */
$addressRepository = $objectManager->create(\Magento\Customer\Api\AddressRepositoryInterface::class);

/** @var \Magento\Quote\Model\Quote\Address $quoteShippingAddress */
$quoteShippingAddress = $objectManager->create(\Magento\Quote\Model\Quote\Address::class);
$quoteShippingAddress->importCustomerAddressData($addressRepository->getById(1));

/** @var $quote \Magento\Quote\Model\Quote */
$quote = $objectManager->create(\Magento\Quote\Model\Quote::class);
$quote->setCustomerEmail('admin@example.com');
$quote->setStoreId(1);
$quote->assignCustomerWithAddressChange($customer);
$quote->setReservedOrderId('test_order_1');
$quote->setBillingAddress($quoteShippingAddress);
$quote->setShippingAddress($quoteShippingAddress);

/** @var \Magento\Quote\Api\CartRepositoryInterface $quoteRepository */
$quoteRepository = $objectManager->create(\Magento\Quote\Api\CartRepositoryInterface::class);
$quoteRepository->save($quote);

/** @var \Magento\Quote\Api\Data\CartItemInterfaceFactory $cartItemFactory */
$cartItemFactory = $objectManager->get(\Magento\Quote\Api\Data\CartItemInterfaceFactory::class);

/** @var \Magento\Quote\Api\Data\CartItemInterface $cartItem */
$cartItem = $cartItemFactory->create();
$cartItem->setQty(2);
$cartItem->setQuoteId($quote->getId());
$cartItem->setSku($product->getSku());
$cartItem->setProductType(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE);

/** @var \Magento\Quote\Api\CartItemRepositoryInterface $cartItemRepository */
$cartItemRepository = $objectManager->get(\Magento\Quote\Api\CartItemRepositoryInterface::class);
$cartItemRepository->save($cartItem);

$quote = $quoteRepository->get($quote->getId());

/** @var  \Magento\GiftCardAccount\Model\Giftcardaccount $giftCardAccount */
$giftCardAccount = $objectManager->create(\Magento\GiftCardAccount\Model\Giftcardaccount::class);
$giftCardAccount->loadByCode('giftcardaccount_fixture');
$giftCardAccount->addToCart(true, $quote);
