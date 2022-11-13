<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\CatalogStaging\Api\CategoryStagingInterface;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\UpdateFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Staging\Model\VersionManager;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/Catalog/_files/category.php');

$objectManager = Bootstrap::getObjectManager();
$updateFactory = $objectManager->get(UpdateFactory::class);
$updateRepository = $objectManager->get(UpdateRepositoryInterface::class);
$categoryStaging = $objectManager->get(CategoryStagingInterface::class);
$categoryRepository = $objectManager->get(CategoryRepositoryInterface::class);
$versionManager = $objectManager->get(VersionManager::class);

$startTime = date('Y-m-d H:i:s', strtotime('+1 day'));
$endTime = date('Y-m-d H:i:s', strtotime('+2 days'));
$updateId = strtotime($startTime);

$updateData = [
    'id' => $updateId,
    'name' => 'Preview Category Staging',
    'start_time' => $startTime,
    'end_time' => $endTime,
    'is_campaign' => 0,
    'is_rollback' => null,
];

$update = $updateFactory->create(['data' => $updateData]);
$updateRepository->save($update);

$categoryId = 333;
$versionManager->setCurrentVersionId($updateId);
$category = $categoryRepository->get($categoryId)->setNewsFromDate(date('Y-m-d H:i:s'));
$category->setName('new category update');

$categoryStaging->schedule($category, $updateId, ['created_in' => $updateId, 'store_id' => '1']);

$startTime = date('Y-m-d H:i:s', strtotime('+2 day'));
$endTime = date('Y-m-d H:i:s', strtotime('+3 days'));
$updateId = strtotime($startTime);

$updateData = [
    'id' => $updateId,
    'name' => 'Preview Disabled Category Staging',
    'start_time' => $startTime,
    'end_time' => $endTime,
    'is_campaign' => 0,
    'is_rollback' => null,
];

$update = $updateFactory->create(['data' => $updateData]);
$updateRepository->save($update);

$categoryId = 333;
$versionManager->setCurrentVersionId($updateId);
$category = $categoryRepository->get($categoryId)->setNewsFromDate(date('Y-m-d H:i:s'));
$category->setIsActive(false);

$categoryStaging->schedule($category, $updateId, ['created_in' => $updateId, 'store_id' => '1']);
