<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

/** @var \Magento\Catalog\Model\Category $category */
$category = $objectManager->create(\Magento\Catalog\Model\Category::class);
$category->isObjectNew(true);
$category->setId(33)
    ->setCreatedAt('2017-01-01 01:01:01')
    ->setName('Category 1')
    ->setParentId(2)
    ->setPath('1/2/33')
    ->setLevel(2)
    ->setAvailableSortBy(['position', 'name'])
    ->setDefaultSortBy('name')
    ->setIsActive(true)
    ->setPosition(1)
    ->save();

$category = $objectManager->create(\Magento\Catalog\Model\Category::class);
$category->isObjectNew(true);
$category->setId(44)
    ->setCreatedAt('2017-01-01 01:01:01')
    ->setName('Category 2')
    ->setParentId(2)
    ->setPath('1/2/44')
    ->setLevel(2)
    ->setAvailableSortBy(['position', 'name'])
    ->setDefaultSortBy('name')
    ->setIsActive(true)
    ->setPosition(1)
    ->save();

$category = $objectManager->create(\Magento\Catalog\Model\Category::class);
$category->isObjectNew(true);
$category->setId(111)
    ->setCreatedAt('2017-01-01 01:01:01')
    ->setName('Category 1.2')
    ->setParentId(33)
    ->setPath('1/2/33/111')
    ->setLevel(3)
    ->setAvailableSortBy(['position', 'name'])
    ->setDefaultSortBy('name')
    ->setIsActive(true)
    ->setPosition(1)
    ->save();

$eavSetupFactory = $objectManager->create(\Magento\Eav\Setup\EavSetupFactory::class);
/** @var \Magento\Eav\Setup\EavSetup $eavSetup */
$eavSetup = $eavSetupFactory->create();
$eavSetup->addAttribute(
    \Magento\Catalog\Model\Product::ENTITY,
    'promo_attribute',
    [
        'label' => 'Promo-attribute',
        'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
        'required' => 0,
        'user_defined' => 1,
        'used_in_product_listing' => 1,
        'used_for_promo_rules' => 1,
        'input' => 'text',
        'group' => 'attributes', // attribute set is required to be set for attribute, which used for target rule
    ]
);
$eavSetup->addAttribute(
    \Magento\Catalog\Model\Product::ENTITY,
    'global_attribute',
    [
        'label' => 'Global-attribute',
        'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
        'required' => 0,
        'user_defined' => 1,
        'used_in_product_listing' => 1,
        'used_for_promo_rules' => 1,
        'input' => 'text',
        'type' => 'int',
        'group' => 'attributes', // attribute set is required to be set for attribute, which used for target rule
    ]
);
/** @var \Magento\Eav\Model\Config $eavConfig */
$eavConfig = $objectManager->get(\Magento\Eav\Model\Config::class);
$eavConfig->clear();

$productIds = [];
for ($i = 1; $i <= 4; $i++) {
    $categoryId = ($i % 2 ? 33 : 44);
    /** @var $product \Magento\Catalog\Model\Product */
    $product = $objectManager->create(\Magento\Catalog\Model\Product::class);
    $product->isObjectNew(true);
    $product->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE)
        ->setAttributeSetId(4)
        ->setName('Simple Product ' . $i)
        ->setSku('simple' . $i)
        ->setPrice(10)
        ->setWeight(1)
        ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
        ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
        ->setWebsiteIds([1])
        ->setCategoryIds([$categoryId])
        ->setStockData(['use_config_manage_stock' => 1, 'qty' => 100, 'is_qty_decimal' => 0, 'is_in_stock' => 1])
        ->save();

    $productIds[] = $product->getId();
}

$attributes = [
    'promo_attribute' => 'RELATED_PRODUCT',
    'global_attribute' => 666,
];
/** @var \Magento\Store\Model\Store $store */
$store = $objectManager->get(\Magento\Store\Model\StoreManager::class)->getStore('default');
/** @var \Magento\Catalog\Model\Product\Action $productAction */
$productAction = $objectManager->create(\Magento\Catalog\Model\Product\Action::class);
$productAction->updateAttributes($productIds, $attributes, $store->getId());

/** @var $product \Magento\Catalog\Model\Product */
$product = $objectManager->create(\Magento\Catalog\Model\Product::class);
$product->isObjectNew(true);
$product->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE)
    ->setAttributeSetId(4)
    ->setName('Child Simple Product')
    ->setSku('child_simple')
    ->setPrice(10)
    ->setWeight(1)
    ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
    ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
    ->setWebsiteIds([1])
    ->setCategoryIds([111])
    ->setStockData(['use_config_manage_stock' => 1, 'qty' => 100, 'is_qty_decimal' => 0, 'is_in_stock' => 1])
    ->save();
$productAction->updateAttributes(
    [$product->getId()],
    [
        'promo_attribute' => 'ANOTHER_PRODUCT',
        'global_attribute' => 777,
    ],
    0
);
