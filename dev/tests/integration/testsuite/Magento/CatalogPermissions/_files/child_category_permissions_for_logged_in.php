<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
Resolver::getInstance()->requireDataFixture('Magento/CatalogPermissions/_files/category.php');

$category = $objectManager->create(\Magento\Catalog\Model\Category::class);
$category->isObjectNew(true);
$category->setId(5)
    ->setCreatedAt('2014-06-23 09:50:07')
    ->setName('Allow child category')
    ->setParentId(3)
    ->setPath('1/2/3/5')
    ->setLevel(3)
    ->setAvailableSortBy('name')
    ->setDefaultSortBy('name')
    ->setIsActive(true)
    ->setPosition(1)
    ->setAvailableSortBy(['position'])
    ->setIsAnchor(1)
    ->save();

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
    ->setGrantCatalogCategoryView(\Magento\CatalogPermissions\Model\Permission::PERMISSION_DENY)
    ->setGrantCatalogProductPrice(\Magento\CatalogPermissions\Model\Permission::PERMISSION_DENY)
    ->setGrantCheckoutItems(\Magento\CatalogPermissions\Model\Permission::PERMISSION_DENY)
    ->save();
