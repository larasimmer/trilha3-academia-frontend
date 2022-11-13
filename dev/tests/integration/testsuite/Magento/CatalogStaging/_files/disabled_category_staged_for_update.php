<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogStaging\Api\ProductStagingInterface;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\CatalogStaging\Api\CategoryStagingInterface;
use Magento\Staging\Model\UpdateFactory;
use Magento\Staging\Model\VersionManager;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
$updateFactory = $objectManager->get(UpdateFactory::class);
$updateRepository = $objectManager->get(UpdateRepositoryInterface::class);
$productStaging = $objectManager->get(ProductStagingInterface::class);
$versionManager = $objectManager->get(VersionManager::class);
$productRepository = $objectManager->get(ProductRepositoryInterface::class);

//Stage changes to the inactive category
$startTime = date('Y-m-d H:i:s', strtotime('+1 day'));
$updateId = strtotime($startTime);
$updateData = [
    'id' => $updateId,
    'name' => 'Update for Category 8 Staging',
    'start_time' => $startTime,
    'is_campaign' => 0,
    'is_rollback' => null,
];

$update = $updateFactory->create(['data' => $updateData]);
$updateRepository->save($update);

$categoryId = 8;
$versionManager->setCurrentVersionId($updateId);
$categoryRepository = $objectManager->get(CategoryRepositoryInterface::class);

/** @var \Magento\Catalog\Api\Data\CategoryInterface $category */
$category = $categoryRepository->get($categoryId, 0);
$category->setIsActive(true);
/** @var CategoryStagingInterface $categoryStaging */
$categoryStaging = $objectManager->get(CategoryStagingInterface::class);
$categoryStaging->schedule($category, $updateId);
