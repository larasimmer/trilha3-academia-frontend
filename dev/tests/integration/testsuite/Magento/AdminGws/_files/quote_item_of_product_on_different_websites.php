<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Indexer\Product\Price\Processor;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteIdMask;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/AdminGws/_files/two_roles_for_different_websites.php');

$objectManager = Bootstrap::getObjectManager();

$websiteRepository = $objectManager->create(WebsiteRepositoryInterface::class);
$secondWebsite = $websiteRepository->get('test_website');
$storeRepository = $objectManager->create(StoreRepositoryInterface::class);
$defaultStore = $storeRepository->get('default');
$secondStore = $storeRepository->get('test_store_view');

/*
 * Creation of Quote on Test Websites
 */
/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->create(ProductRepositoryInterface::class);

/** @var ProductInterface $product */
$product = $objectManager->create(ProductInterface::class);
$product->setTypeId('simple')
    ->setName('Simple Product Report')
    ->setSku('simple_report')
    ->setWebsiteIds([1, $secondWebsite->getId()])
    ->setPrice(123)
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

$product = $productRepository->get('simple_report');
$product->setStoreId($secondStore->getId());
$product->setPrice(123);
$productRepository->save($product);

$product = $productRepository->get('simple_report');
$product->setStoreId($defaultStore->getId());
$product->setPrice(321);
$productRepository->save($product);

/** @var Quote $quote */
$quote = $objectManager->create(Quote::class);
$quote->setReservedOrderId('test_order_item_1');
$quote->setStoreId($secondStore->getId());
$quote->save();
$quote->addProduct($product, 1);
$quote->collectTotals()->save();

/** @var QuoteIdMask $quoteIdMask */
$quoteIdMask = $objectManager->create(QuoteIdMaskFactory::class)->create();
$quoteIdMask->setQuoteId($quote->getId());
$quoteIdMask->setDataChanges(true);
$quoteIdMask->save();

/** @var Processor $priceIndexerProcessor */
$priceIndexerProcessor = $objectManager->get(Processor::class);
$priceIndexerProcessor->reindexAll();
