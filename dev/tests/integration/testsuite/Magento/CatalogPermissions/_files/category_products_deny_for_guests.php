<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
$productRepository = $objectManager->create(\Magento\Catalog\Api\ProductRepositoryInterface::class);

$category = $objectManager->create(\Magento\Catalog\Model\Category::class);
$category->isObjectNew(true);
$category->setId(4)
    ->setCreatedAt('2014-06-23 09:50:07')
    ->setName('Deny category for guests')
    ->setParentId(2)
    ->setPath('1/2/4')
    ->setLevel(2)
    ->setAvailableSortBy('name')
    ->setDefaultSortBy('name')
    ->setIsActive(true)
    ->setPosition(1)
    ->setAvailableSortBy(['position'])
    ->save();

/** @var $product \Magento\Catalog\Model\Product */
$product = $objectManager->create(\Magento\Catalog\Model\Product::class);
$product->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE)
    ->setId(133)
    ->setAttributeSetId(4)
    ->setStoreId(1)
    ->setWebsiteIds([1])
    ->setName('Deny category product for guests')
    ->setSku('simple_deny_122')
    ->setPrice(111)
    ->setWeight(18)
    ->setStockData(['use_config_manage_stock' => 0])
    ->setCategoryIds([4])
    ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
    ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);

$productRepository->save($product);

/**
 * Deny category for guests
 */
/** @var $permission \Magento\CatalogPermissions\Model\Permission */
$permission = $objectManager->create(\Magento\CatalogPermissions\Model\Permission::class);
$permission->setEntityId(1)
    ->setWebsiteId(1)
    ->setCategoryId(4)
    ->setCustomerGroupId(0)
    ->setGrantCatalogCategoryView(\Magento\CatalogPermissions\Model\Permission::PERMISSION_DENY)
    ->setGrantCatalogProductPrice(\Magento\CatalogPermissions\Model\Permission::PERMISSION_DENY)
    ->setGrantCheckoutItems(\Magento\CatalogPermissions\Model\Permission::PERMISSION_DENY)
    ->save();

/**
 * Allow category for default customer group
 */
/** @var $permission \Magento\CatalogPermissions\Model\Permission */
$permission = $objectManager->create(\Magento\CatalogPermissions\Model\Permission::class);
$permission->setEntityId(2)
    ->setWebsiteId(1)
    ->setCategoryId(4)
    ->setCustomerGroupId(1)
    ->setGrantCatalogCategoryView(\Magento\CatalogPermissions\Model\Permission::PERMISSION_ALLOW)
    ->setGrantCatalogProductPrice(\Magento\CatalogPermissions\Model\Permission::PERMISSION_PARENT)
    ->setGrantCheckoutItems(\Magento\CatalogPermissions\Model\Permission::PERMISSION_PARENT)
    ->save();
