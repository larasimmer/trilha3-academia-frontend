<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Catalog\Model\Category;
use Magento\CatalogStaging\Api\CategoryStagingInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

/** @var StoreManagerInterface $storeManager */
$storeManager = $objectManager->get(StoreManagerInterface::class);
$defaultStoreId = $storeManager->getStore()->getId();

/** @var Category $category */
$category = $objectManager->create(Category::class);
$category->isObjectNew(true);
$category
    ->setId(15)
    ->setStoreId($defaultStoreId)
    ->setIncludeInMenu(true)
    ->setName('Category_en')
    ->setDescription('Category_en Description')
    ->setDisplayMode(Category::DM_MIXED)
    ->setAvailableSortBy(['name', 'price'])
    ->setDefaultSortBy('name')
    ->setUrlKey('category-en')
    ->setMetaTitle('Category_en Meta Title')
    ->setMetaKeywords('Category_en Meta Keywords')
    ->setMetaDescription('Category_en Meta Description')
    ->setParentId(2)
    ->setPath('1/2/3')
    ->setLevel(2)
    ->setIsActive(true)
    ->setPosition(1)
    ->setAvailableSortBy(['position']);
$category->save();

/** @var CategoryStagingInterface $staging */
$staging = $objectManager->create(CategoryStagingInterface::class);

$changes = [
    'name' => 'Category_en Updated',
    'image' => '',
    'description' => '<p>Category_en Description Updated</p>',
    'display_mode' => 'PAGE',
    'is_anchor' => 1,
    'available_sort_by' => ['position'],
    'default_sort_by' => 'position',
    'url_key' => 'category-en-Updated',
    'meta_title' => 'Category_en Meta Title Updated',
    'meta_keywords' => 'Category_en Meta Keywords Updated',
    'meta_description' => 'Category_en Meta Description Updated',
];

$staging->schedule($category, 1, $changes);
