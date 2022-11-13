<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Catalog\Api\CategoryListInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Staging\Model\VersionHistoryInterface;
use Magento\Staging\Model\VersionManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

/** @var ObjectManagerInterface $objectManager */
$objectManager = Bootstrap::getObjectManager();
/** @var CategoryRepositoryInterface $categoryRepository */
$categoryRepository = $objectManager->get(CategoryRepositoryInterface::class);
/** @var SearchCriteriaBuilder $searchCriteriaBuilder */
$searchCriteriaBuilder = $objectManager->get(SearchCriteriaBuilder::class);
/** @var CategoryListInterface $categoryList */
$categoryList = $objectManager->get(CategoryListInterface::class);
/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

try {
    $searchCriteria = $searchCriteriaBuilder->addFilter('name', 'Category with staging update')->create();
    $category = current($categoryList->getList($searchCriteria)->getItems());
    $categoryRepository->delete($category);
} catch (\Throwable $e) {
    // Nothing to delete
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);

/** @var VersionHistoryInterface $versionHistory */
$versionHistory = $objectManager->get(VersionHistoryInterface::class);
$versionHistory->setCurrentId(VersionManager::MIN_VERSION);

Resolver::getInstance()->requireDataFixture('Magento/Staging/_files/staging_update_rollback.php');
