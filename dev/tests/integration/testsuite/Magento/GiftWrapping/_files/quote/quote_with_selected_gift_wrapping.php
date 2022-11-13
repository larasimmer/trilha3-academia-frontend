<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

use Magento\TestFramework\Workaround\Override\Fixture\Resolver;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResource;
use Magento\Quote\Model\Quote;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Quote\Model\QuoteIdMask;
use Magento\Quote\Model\ResourceModel\Quote\QuoteIdMask as QuoteIdMaskResource;
use Magento\Quote\Model\ResourceModel\Quote\QuoteIdMaskFactory;
use Magento\GiftWrapping\Model\Wrapping as GiftWrapping;
use Magento\GiftWrapping\Model\ResourceModel\Wrapping as GiftWrappingResource;

Resolver::getInstance()
    ->requireDataFixture('Magento/GiftWrapping/_files/quote/quote_with_selected_gift_wrapping_rollback.php');
Resolver::getInstance()->requireDataFixture('Magento/GiftWrapping/_files/wrappings.php');
Resolver::getInstance()->requireDataFixture('Magento/Catalog/_files/products.php');

/** @var ObjectManagerInterface $objectManager */
$objectManager = Bootstrap::getObjectManager();

/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->create(ProductRepositoryInterface::class);
$product = $productRepository->get('simple');

/**
 * @var GiftWrappingResource $giftWrappingResource
 * @var GiftWrapping $giftWrappingForCart
 * @var GiftWrapping $giftWrappingForItem
 */
$giftWrappingResource = $objectManager->create(GiftWrappingResource::class);
$giftWrappingForCart = $objectManager->create(GiftWrapping::class);
$giftWrappingResource->load($giftWrappingForCart, 'image1.png', 'image');
$giftWrappingForItem = $objectManager->create(GiftWrapping::class);
$giftWrappingResource->load($giftWrappingForItem, 'image2.png', 'image');

/**
 * @var QuoteResource $quote
 * @var Quote $quoteModel
 */
$quote = $objectManager->create(QuoteResource::class);
$quoteModel = $objectManager->create(Quote::class);
$quoteModel->setData(['store_id' => 1, 'is_active' => 1, 'is_multi_shipping' => 0]);
$quote->save($quoteModel);
$quoteModel
    ->setReservedOrderId('test_quote_with_selected_gift_wrapping')
    ->setGwId($giftWrappingForCart->getWrappingId())
    ->setGwAllowGiftReceipt(0)
    ->setGwAddCard(1)
    ->addProduct($product, 1)->setGwId($giftWrappingForItem->getWrappingId());
$quote->save($quoteModel);

/**
 * @var QuoteIdMaskResource $quoteIdMask
 * @var QuoteIdMask $quoteIdMaskModel
 */
$quoteIdMask = Bootstrap::getObjectManager()
    ->create(QuoteIdMaskFactory::class)
    ->create();
$quoteIdMaskModel = $objectManager->create(QuoteIdMask::class);
$quoteIdMaskModel->setQuoteId($quoteModel->getId());
$quoteIdMaskModel->setDataChanges(true);
$quoteIdMask->save($quoteIdMaskModel);
