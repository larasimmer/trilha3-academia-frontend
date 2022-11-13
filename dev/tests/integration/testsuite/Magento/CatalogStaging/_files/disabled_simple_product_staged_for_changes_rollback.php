<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Registry;
use Magento\Staging\Model\ResourceModel\Update;
use Magento\Staging\Model\UpdateFactory;
use Magento\Staging\Model\UpdateRepository;
use Magento\Catalog\Api\CategoryListInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Staging\Model\VersionManager;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
$updateFactory = $objectManager->get(UpdateFactory::class);
$updateResourceModel = $objectManager->get(Update::class);
$updateRepository = $objectManager->get(UpdateRepository::class);
$productRepository = $objectManager->get(ProductRepositoryInterface::class);
$registry = Bootstrap::getObjectManager()->get(Registry::class);
$versionManager = $objectManager->get(VersionManager::class);

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

$product = $productRepository->get('disabled-simple');
$productRepository->delete($product);

$update = $updateFactory->create();
$updateResourceModel->load($update, 'Disabled Product Staging Test', 'name');
$versionManager->setCurrentVersionId($update->getId());

// Remove products
try {
    $product = $productRepository->get('disabled-simple');
    $productRepository->delete($product);
} catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
    //Product already removed
}
$updateRepository->delete($update);

/** @var SearchCriteriaBuilder $searchCriteriaBuilder */
$searchCriteriaBuilder = $objectManager->get(SearchCriteriaBuilder::class);
$searchCriteria = $searchCriteriaBuilder->addFilter('name', 'Category For Disabled Product')->create();

/** @var CategoryListInterface $categoryList */
$categoryList = $objectManager->get(CategoryListInterface::class);
$categories = $categoryList->getList($searchCriteria)->getItems();

/** @var CategoryRepositoryInterface $categoryRepository */
$categoryRepository = $objectManager->get(CategoryRepositoryInterface::class);

foreach ($categories as $category) {
    $categoryRepository->delete($category);
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
