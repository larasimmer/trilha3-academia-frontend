<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

use Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollection;
use Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollectionFactory;
use Magento\UrlRewrite\Model\UrlRewrite;

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

$registry = $objectManager->get(\Magento\Framework\Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->create(\Magento\Catalog\Api\ProductRepositoryInterface::class);
foreach (['simple1', 'simple2', 'simple3', 'simple4', 'child_simple'] as $sku) {
    try {
        $product = $productRepository->get($sku, false, null, true);
        $productRepository->delete($product);
    } catch (\Magento\Framework\Exception\NoSuchEntityException $exception) {
        //Product already removed
    }
}

$eavSetupFactory = $objectManager->create(\Magento\Eav\Setup\EavSetupFactory::class);
/** @var \Magento\Eav\Setup\EavSetup $eavSetup */
$eavSetup = $eavSetupFactory->create();
$eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, 'promo_attribute');
$eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, 'global_attribute');

/** @var $category \Magento\Catalog\Model\Category */
$category = $objectManager->create(\Magento\Catalog\Model\Category::class);
$category->load(111);
if ($category->getId()) {
    $category->delete();
}
$category->load(44);
if ($category->getId()) {
    $category->delete();
}
$category->load(33);
if ($category->getId()) {
    $category->delete();
}

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
$website = $objectManager->get(Magento\Store\Model\Website::class);
$website->load('wrapping_website', 'code');

if ($website->getId()) {
    $website->delete();
}

/** @var UrlRewriteCollectionFactory $urlRewriteCollectionFactory */
$urlRewriteCollectionFactory = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(
    UrlRewriteCollectionFactory::class
);
/** @var UrlRewriteCollection $urlRewriteCollection */
$urlRewriteCollection = $urlRewriteCollectionFactory->create();
$urlRewriteCollection->addFieldToFilter('entity_type', ['eq' => 'product']);
$urlRewrites = $urlRewriteCollection->getItems();
/** @var UrlRewrite $urlRewrite */
foreach ($urlRewrites as $urlRewrite) {
    try {
        $urlRewrite->delete();
    } catch (\Exception $exception) {
        // already removed
    }
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
