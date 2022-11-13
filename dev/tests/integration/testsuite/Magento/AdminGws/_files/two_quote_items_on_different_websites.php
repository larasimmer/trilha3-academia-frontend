<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteIdMask;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/AdminGws/_files/two_roles_for_different_websites.php');

$objectManager = Bootstrap::getObjectManager();

$websiteRepository = $objectManager->create(\Magento\Store\Api\WebsiteRepositoryInterface::class);
$secondWebsite = $websiteRepository->get('test_website');
$storeRepository = $objectManager->create(\Magento\Store\Api\StoreRepositoryInterface::class);
$secondStore = $storeRepository->get('test_store_view');

/*
 * Creation of Quotes on Default and Test Websites
 */
/** @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->create(\Magento\Catalog\Api\ProductRepositoryInterface::class);

/** @var ProductInterface $product */
$product = $objectManager->create(ProductInterface::class);
$product->setTypeId('simple')
    ->setName('Simple Product One')
    ->setSku('simple_one')
    ->setWebsiteIds([1])
    ->setPrice(10)
    ->setMetaTitle('meta title')
    ->setMetaKeyword('meta keyword')
    ->setMetaDescription('meta description')
    ->setVisibility(Visibility::VISIBILITY_BOTH)
    ->setStatus(Status::STATUS_ENABLED)
    ->setStockData(['use_config_manage_stock' => 0])
    ->setAttributeSetId(4)
    ->setIsSalable(true)
    ->setSalable(true);
$product = $productRepository->save($product);

/** @var Quote $quote */
$quote = $objectManager->create(Quote::class);
$quote->setReservedOrderId('test_order_item_1');
$quote->save();
$quote->addProduct($product, 1);
$quote->collectTotals()->save();

/** @var QuoteIdMask $quoteIdMask */
$quoteIdMask = $objectManager->create(QuoteIdMaskFactory::class)->create();
$quoteIdMask->setQuoteId($quote->getId());
$quoteIdMask->setDataChanges(true);
$quoteIdMask->save();

/** @var ProductInterface $product */
$product = $objectManager->create(ProductInterface::class);
$product->setTypeId('simple')
    ->setName('Simple Product Two')
    ->setSku('simple_two')
    ->setWebsiteIds([$secondWebsite->getId()])
    ->setPrice(20)
    ->setMetaTitle('meta title')
    ->setMetaKeyword('meta keyword')
    ->setMetaDescription('meta description')
    ->setVisibility(Visibility::VISIBILITY_BOTH)
    ->setStatus(Status::STATUS_ENABLED)
    ->setStockData(['use_config_manage_stock' => 0])
    ->setAttributeSetId(4)
    ->setIsSalable(true)
    ->setSalable(true);
$product = $productRepository->save($product);

/** @var Quote $quote */
$quote = $objectManager->create(Quote::class);
$quote->setReservedOrderId('test_order_item_2');
$quote->setStoreId($secondStore->getId());
$quote->save();
$quote->addProduct($product, 1);
$quote->collectTotals()->save();

/** @var QuoteIdMask $quoteIdMask */
$quoteIdMask = $objectManager->create(QuoteIdMaskFactory::class)->create();
$quoteIdMask->setQuoteId($quote->getId());
$quoteIdMask->setDataChanges(true);
$quoteIdMask->save();
