<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\TestFramework\Catalog\Model\GetCategoryByName;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\CatalogPermissions\Model\Permission;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/Catalog/_files/category.php');

$objectManager = Bootstrap::getObjectManager();
/** @var GetCategoryByName $getCategoryByName */
$getCategoryByName = $objectManager->create(GetCategoryByName::class);
/** @var $permission Permission */
$permission = $objectManager->create(Permission::class);
$websiteId = $objectManager
    ->get(StoreManagerInterface::class)
    ->getWebsite()
    ->getId();
$permission->setEntityId(1)
    ->setWebsiteId($websiteId)
    ->setCategoryId($getCategoryByName->execute('Category 1')->getId())
    ->setCustomerGroupId(1)
    ->setGrantCatalogCategoryView(Permission::PERMISSION_DENY)
    ->setGrantCatalogProductPrice(Permission::PERMISSION_DENY)
    ->setGrantCheckoutItems(Permission::PERMISSION_DENY)
    ->save();
