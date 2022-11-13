<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\CatalogStaging\Api\ProductStagingInterface;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\UpdateFactory;
use Magento\Staging\Model\VersionManager;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
$updateFactory = $objectManager->get(UpdateFactory::class);
$updateRepository = $objectManager->get(UpdateRepositoryInterface::class);
$productStaging = $objectManager->get(ProductStagingInterface::class);
$versionManager = $objectManager->get(VersionManager::class);
$productRepository = $objectManager->get(ProductRepositoryInterface::class);

//create product
/** @var Product $product */
$product = $objectManager->create(Product::class);
$product->setTypeId(Type::TYPE_SIMPLE)
    ->setAttributeSetId(4)
    ->setWebsiteIds([1])
    ->setName('Simple Product 1')
    ->setSku('simple')
    ->setPrice(10)
    ->setQty(100)
    ->setUrlKey('simple-' . rand(10, 1000))
    ->setDescription('Description with <b>html tag</b>')
    ->setVisibility(Visibility::VISIBILITY_BOTH)
    ->setStatus(Status::STATUS_ENABLED)
    ->setStockData(['use_config_manage_stock' => 0])
    ->setCanSaveCustomOptions(true)
    ->setHasOptions(true);
$productRepository->save($product);

//Stage changes
$startTime = date('Y-m-d H:i:s', strtotime('+1 day'));
$endTime = date('Y-m-d H:i:s', strtotime('+10 days'));
$updateData = [
    'name' => 'Product Update Test',
    'start_time' => $startTime,
    'end_time' => $endTime,
    'is_campaign' => 0,
    'is_rollback' => null,
];

$update = $updateFactory->create(['data' => $updateData]);
$updateRepository->save($update);
$product = $productRepository->get('simple');

$versionManager->setCurrentVersionId($update->getId());
$product->setName('Updated Product')->setPrice(5.99);
$productStaging->schedule($product, $update->getId());
