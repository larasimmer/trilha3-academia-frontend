<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\CatalogStaging\Api\ProductStagingInterface;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\UpdateFactory;
use Magento\Staging\Model\VersionManager;

/** @var \Magento\TestFramework\ObjectManager $objectManager */
$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

/** @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);

// Create custom attribute
/** @var $installer \Magento\Catalog\Setup\CategorySetup */
$installer = $objectManager->create(\Magento\Catalog\Setup\CategorySetup::class);
$entityModel = $objectManager->create(\Magento\Eav\Model\Entity::class);
$attributeSetId = $installer->getAttributeSetId('catalog_product', 'Default');
$entityTypeId = $entityModel->setType(\Magento\Catalog\Model\Product::ENTITY)->getTypeId();
$groupId = $installer->getDefaultAttributeGroupId($entityTypeId, $attributeSetId);
/** @var $attribute \Magento\Catalog\Model\ResourceModel\Eav\Attribute */
$attribute = $objectManager->create(\Magento\Catalog\Model\ResourceModel\Eav\Attribute::class);
$attribute->setAttributeCode('attribute_code_custom')
    ->setEntityTypeId($entityTypeId)
    ->setIsVisible(true)
    ->setFrontendInput('text')
    ->setFrontendLabel('custom_attributes_frontend_label')
    ->setAttributeSetId($attributeSetId)
    ->setAttributeGroupId($groupId)
    ->setIsFilterable(1)
    ->setIsUserDefined(1)
    ->setBackendType($attribute->getBackendTypeByInput($attribute->getFrontendInput()))
    ->save();

// Create simple product with custom attribute
/** @var $product \Magento\Catalog\Model\Product */
$product = $objectManager->create(\Magento\Catalog\Model\Product::class);
$product->isObjectNew(true);
$product->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE)
    ->setAttributeSetId($attributeSetId)
    ->setWebsiteIds([1])
    ->setName('Simple Product')
    ->setSku('simple')
    ->setPrice(10)
    ->setWeight(1)
    ->setShortDescription("Short description")
    ->setDescription('Description with <b>html tag</b>')
    ->setMetaTitle('meta title')
    ->setMetaKeyword('meta keyword')
    ->setMetaDescription('meta description')
    ->setVisibility(Visibility::VISIBILITY_BOTH)
    ->setStatus(Status::STATUS_ENABLED)
    ->setStockData(
        [
            'use_config_manage_stock'   => 1,
            'qty'                       => 100,
            'is_qty_decimal'            => 0,
            'is_in_stock'               => 1,
        ]
    );
$product->setCustomAttribute($attribute->getAttributeCode(), 'customAttributeValue');
$product = $productRepository->save($product);

//Stage changes
$startTime = date('Y-m-d H:i:s', strtotime('+2 days'));
$endTime = date('Y-m-d H:i:s', strtotime('+4 days'));
$updateData = [
    'name' => 'Simple Product2 Update After CatalogRule update',
    'start_time' => $startTime,
    'end_time' => $endTime,
    'is_campaign' => 0,
    'is_rollback' => null,
];

$updateFactory = $objectManager->get(UpdateFactory::class);
$updateRepository = $objectManager->get(UpdateRepositoryInterface::class);
$productStaging = $objectManager->get(ProductStagingInterface::class);
$versionManager = $objectManager->get(VersionManager::class);
$update = $updateFactory->create(['data' => $updateData]);
$updateRepository->save($update);
$product = $productRepository->get('simple');

$versionManager->setCurrentVersionId($update->getId());
$product->setName('Updated A Simple Product2 Name')->setPrice(6);
$productStaging->schedule($product, $update->getId());
