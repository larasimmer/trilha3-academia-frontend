<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\CatalogPermissions\Model\Indexer\Category\Processor as CategoryIndexerProcessor;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Catalog\Model\Category;
use Magento\CatalogPermissions\Model\Permission;

$category = Bootstrap::getObjectManager()->create(Category::class);
$category->isObjectNew(true);
$category->setId(3)
    ->setName('Allow category')
    ->setParentId(2)
    ->setPath('1/2/3')
    ->setLevel(2)
    ->setIsActive(true)
    ->save();

$category = Bootstrap::getObjectManager()->create(Category::class);
$category->isObjectNew(true);
$category->setId(4)
    ->setName('Deny category')
    ->setParentId(2)
    ->setPath('1/2/4')
    ->setLevel(2)
    ->setIsActive(true)
    ->save();

$permission = Bootstrap::getObjectManager()->create(Permission::class);
$permission->setWebsiteId(1)
    ->setCategoryId(3)
    ->setCustomerGroupId(0)
    ->setGrantCatalogCategoryView(Permission::PERMISSION_ALLOW)
    ->setGrantCatalogProductPrice(Permission::PERMISSION_ALLOW)
    ->setGrantCheckoutItems(Permission::PERMISSION_ALLOW)
    ->save();

$permission = Bootstrap::getObjectManager()->create(Permission::class);
$permission->setWebsiteId(1)
    ->setCategoryId(4)
    ->setCustomerGroupId(0)
    ->setGrantCatalogCategoryView(Permission::PERMISSION_DENY)
    ->setGrantCatalogProductPrice(Permission::PERMISSION_DENY)
    ->setGrantCheckoutItems(Permission::PERMISSION_DENY)
    ->save();

$categoryIndexerProcessor = Bootstrap::getObjectManager()->get(CategoryIndexerProcessor::class);
$categoryIndexerProcessor->reindexList([3, 4]);
