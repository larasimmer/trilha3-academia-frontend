<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
$productRepository = $objectManager->create(\Magento\Catalog\Api\ProductRepositoryInterface::class);

Resolver::getInstance()->requireDataFixture('Magento/CatalogPermissions/_files/category.php');

/** @var $product \Magento\Catalog\Model\Product */
$product = $objectManager->create(\Magento\Catalog\Model\Product::class);
$product->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE)
    ->setId(122)
    ->setAttributeSetId(4)
    ->setStoreId(1)
    ->setWebsiteIds([1])
    ->setName('Allow category product to cart')
    ->setSku('simple_allow_122')
    ->setPrice(111)
    ->setWeight(18)
    ->setStockData(['use_config_manage_stock' => 0])
    ->setCategoryIds([3])
    ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
    ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);

$productRepository->save($product);

/** @var $product \Magento\Catalog\Model\Product */
$product = $objectManager->create(\Magento\Catalog\Model\Product::class);
$product->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE)
    ->setId(133)
    ->setAttributeSetId(4)
    ->setStoreId(1)
    ->setWebsiteIds([1])
    ->setName('Deny category product to cart')
    ->setSku('simple_deny_122')
    ->setPrice(111)
    ->setWeight(18)
    ->setStockData(['use_config_manage_stock' => 0])
    ->setCategoryIds([4])
    ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
    ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);

$productRepository->save($product);

/** @var $permission \Magento\CatalogPermissions\Model\Permission */
$permission = $objectManager->create(\Magento\CatalogPermissions\Model\Permission::class);
$permission->setEntityId(1)
    ->setWebsiteId(1)
    ->setCategoryId(3)
    ->setCustomerGroupId(1)
    ->setGrantCatalogCategoryView(\Magento\CatalogPermissions\Model\Permission::PERMISSION_ALLOW)
    ->setGrantCatalogProductPrice(\Magento\CatalogPermissions\Model\Permission::PERMISSION_ALLOW)
    ->setGrantCheckoutItems(\Magento\CatalogPermissions\Model\Permission::PERMISSION_ALLOW)
    ->save();

/** @var $permission \Magento\CatalogPermissions\Model\Permission */
$permission = $objectManager->create(\Magento\CatalogPermissions\Model\Permission::class);
$permission->setEntityId(2)
    ->setWebsiteId(1)
    ->setCategoryId(4)
    ->setCustomerGroupId(1)
    ->setGrantCatalogCategoryView(\Magento\CatalogPermissions\Model\Permission::PERMISSION_ALLOW)
    ->setGrantCatalogProductPrice(\Magento\CatalogPermissions\Model\Permission::PERMISSION_ALLOW)
    ->setGrantCheckoutItems(\Magento\CatalogPermissions\Model\Permission::PERMISSION_DENY)
    ->save();

/** @var $permission \Magento\CatalogPermissions\Model\Permission */
$permission = $objectManager->create(\Magento\CatalogPermissions\Model\Permission::class);
$permission->setEntityId(3)
    ->setWebsiteId(1)
    ->setCategoryId(4)
    ->setCustomerGroupId(0)
    ->setGrantCatalogCategoryView(\Magento\CatalogPermissions\Model\Permission::PERMISSION_ALLOW)
    ->setGrantCatalogProductPrice(\Magento\CatalogPermissions\Model\Permission::PERMISSION_ALLOW)
    ->setGrantCheckoutItems(\Magento\CatalogPermissions\Model\Permission::PERMISSION_DENY)
    ->save();
