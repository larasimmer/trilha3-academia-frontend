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
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Catalog\Api\CategoryLinkManagementInterface;

$objectManager = Bootstrap::getObjectManager();
$updateFactory = $objectManager->get(UpdateFactory::class);
$updateRepository = $objectManager->get(UpdateRepositoryInterface::class);
$productStaging = $objectManager->get(ProductStagingInterface::class);
$versionManager = $objectManager->get(VersionManager::class);
$productRepository = $objectManager->get(ProductRepositoryInterface::class);

/** @var CategoryLinkManagementInterface $categoryLinkManagement */
$categoryLinkManagement = $objectManager->create(CategoryLinkManagementInterface::class);

/** @var \Magento\Catalog\Model\Category $category */
$category = $objectManager->create(\Magento\Catalog\Model\Category::class);
$category->isObjectNew(true);
$category->setName('Category For Disabled Product')
    ->setParentId(2)
    ->setLevel(2)
    ->setAvailableSortBy('name')
    ->setIncludeInMenu(true)
    ->setDefaultSortBy('name')
    ->setIsActive(true);

/** @var CategoryRepositoryInterface $categoryRepository */
$categoryRepository = $objectManager->get(CategoryRepositoryInterface::class);
$categoryRepository->save($category);

//create product
/** @var Product $product */
$product = $objectManager->create(Product::class);
$product->setTypeId(Type::TYPE_SIMPLE)
    ->setAttributeSetId(4)
    ->setWebsiteIds([1])
    ->setName('Disabled Simple Product 1')
    ->setSku('disabled-simple')
    ->setPrice(50)
    ->setQty(100)
    ->setUrlKey('simple-' . rand(10, 1000))
    ->setDescription('Description with <b>html tag</b>')
    ->setVisibility(Visibility::VISIBILITY_BOTH)
    ->setStatus(Status::STATUS_DISABLED)
    ->setStockData(['use_config_manage_stock' => 1, 'qty' => 100, 'is_qty_decimal' => 0, 'is_in_stock' => 1])
    ->setCanSaveCustomOptions(true)
    ->setCategoryIds([$category->getEntityId()])
    ->setHasOptions(true);
$productRepository->save($product);

$categoryLinkManagement->assignProductToCategories(
    $product->getSku(),
    [$category->getEntityId()]
);

//Stage changes
$startTime = date('Y-m-d H:i:s', strtotime('+1 day'));
$endTime = date('Y-m-d H:i:s', strtotime('+10 days'));
$updateData = [
    'name' => 'Disabled Product Staging Test',
    'start_time' => $startTime,
    'end_time' => $endTime,
    'is_campaign' => 0,
    'is_rollback' => null,
];

$update = $updateFactory->create(['data' => $updateData]);
$updateRepository->save($update);
$product = $productRepository->get('disabled-simple');

$versionManager->setCurrentVersionId($update->getId());
$product->setName('Enabled Simple Product 1')->setPrice(45)->setStatus(Status::STATUS_ENABLED);
$productStaging->schedule($product, $update->getId());
