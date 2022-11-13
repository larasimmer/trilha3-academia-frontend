<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Staging\Model\UpdateFactory;
use Magento\Staging\Model\ResourceModel\Update;
use Magento\Staging\Model\VersionManager;
use Magento\Staging\Model\UpdateRepository;

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

/** @var \Magento\Framework\Registry $registry */
$registry = $objectManager->get(\Magento\Framework\Registry::class);

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);

foreach (['simple_10', 'simple_20', 'configurable'] as $sku) {
    try {
        $product = $productRepository->get($sku, true);

        $stockStatus = $objectManager->create(\Magento\CatalogInventory\Model\Stock\Status::class);
        $stockStatus->load($product->getEntityId(), 'product_id');
        $stockStatus->delete();

        $productRepository->delete($product);
    } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
        //Product already removed
    }
}
$updateFactory = $objectManager->get(UpdateFactory::class);
$versionManager = $objectManager->get(VersionManager::class);
$updateResourceModel = $objectManager->get(Update::class);
$updateRepository = $objectManager->get(UpdateRepository::class);

$update = $updateFactory->create();
$updateResourceModel->load($update, 'Configurable Product Update Test', 'name');
$versionManager->setCurrentVersionId($update->getId());

foreach (['simple_10', 'simple_20', 'configurable'] as $sku) {
    try {
        $product = $productRepository->get($sku, true);

        $stockStatus = $objectManager->create(\Magento\CatalogInventory\Model\Stock\Status::class);
        $stockStatus->load($product->getEntityId(), 'product_id');
        $stockStatus->delete();

        $productRepository->delete($product);
    } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
        //Product already removed
    }
}

$productCollection = $objectManager->get(\Magento\Catalog\Model\ResourceModel\Product\Collection::class);
foreach ($productCollection as $product) {
    $product->delete();
}

$eavConfig = $objectManager->get(\Magento\Eav\Model\Config::class);
$attribute = $eavConfig->getAttribute('catalog_product', 'test_configurable');
if ($attribute instanceof \Magento\Eav\Model\Entity\Attribute\AbstractAttribute
    && $attribute->getId()
) {
    $attribute->delete();
}
$eavConfig->clear();

$updateRepository->delete($update);

/** @var \Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollection $urlRewriteCollection */
$urlRewriteCollection = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
    ->create(\Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollection::class);

//remove the url from url rewrite table
$collection = $urlRewriteCollection
    ->addFieldToFilter('entity_type', 'product')
    ->addFieldToFilter('request_path', ['configurable-product.html'])
    ->load()
    ->walk('delete');

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
