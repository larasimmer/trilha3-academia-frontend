<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;
use Magento\Catalog\Model\Product\Visibility;

Resolver::getInstance()->requireDataFixture('Magento/ConfigurableProduct/_files/product_configurable.php');
Resolver::getInstance()->requireDataFixture('Magento/CatalogPermissions/_files/category_products_deny_for_guests.php');

/** @var ProductRepositoryInterface $productRepository */
$productRepository = Bootstrap::getObjectManager()->create(ProductRepositoryInterface::class);

/**
 * Have variants visibility increased so they would also appear in search
 */
$simple_10 = $productRepository->get('simple_10');
$simple_10->setVisibility(Visibility::VISIBILITY_BOTH);
$productRepository->save($simple_10);

$simple_20 = $productRepository->get('simple_20');
$simple_20->setVisibility(Visibility::VISIBILITY_BOTH);
$productRepository->save($simple_20);

/**
 * Category Link association parameters
 */
$variantToBeDenied = 'simple_20';
$categoryIdToGetVariantAssignedTo = 4;

/**
 * Make the association
 */
/** @var \Magento\Catalog\Api\CategoryLinkManagementInterface $categoryLinkManagement */
$categoryLinkManagement = Bootstrap::getObjectManager()->create(CategoryLinkManagementInterface::class);
$categoryLinkManagement->assignProductToCategories(
    $variantToBeDenied,
    [$categoryIdToGetVariantAssignedTo]
);
