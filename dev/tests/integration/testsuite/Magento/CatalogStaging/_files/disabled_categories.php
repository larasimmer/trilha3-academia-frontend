<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

$defaultAttributeSet = $objectManager->get(Magento\Eav\Model\Config::class)
    ->getEntityType('catalog_product')
    ->getDefaultAttributeSetId();

$productRepository = $objectManager->create(
    \Magento\Catalog\Api\ProductRepositoryInterface::class
);

/** @var Magento\Catalog\Api\CategoryLinkManagementInterface $categoryLinkManagement */
$categoryLinkManagement = $objectManager->create(\Magento\Catalog\Api\CategoryLinkManagementInterface::class);

$category = $objectManager->create(\Magento\Catalog\Model\Category::class);
$category->isObjectNew(true);
$category->setId(8)
    ->setName('Inactive')
    ->setParentId(2)
    ->setPath('1/2/8')
    ->setLevel(2)
    ->setAvailableSortBy('name')
    ->setDefaultSortBy('name')
    ->setIsActive(false)
    ->setPosition(4)
    ->save();

/** @var \Magento\Catalog\Model\Product $product */
$product = $objectManager->create(\Magento\Catalog\Model\Product::class);
$product->isObjectNew(true);
$product->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE)
    ->setAttributeSetId($defaultAttributeSet)
    ->setWebsiteIds([1])
    ->setName('Simple Product Eight')
    ->setSku('simple-8')
    ->setPrice(100)
    ->setQty(100)
    ->setWeight(18)
    ->setStockData(['use_config_manage_stock' => 0])
    ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
    ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
$productRepository->save($product);

$categoryLinkManagement->assignProductToCategories(
    $product->getSku(),
    [8]
);
