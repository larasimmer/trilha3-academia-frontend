<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollection;

$objectManager = Bootstrap::getObjectManager();
/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

// Remove products
/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);
try {
    $productRepository->deleteById('simple-8');
} catch (NoSuchEntityException $e) {
    //Product already removed
}

//Remove category
/** @var CategoryRepositoryInterface $categoryRepository */
$categoryRepository = $objectManager->get(CategoryRepositoryInterface::class);

/** @var Category $category */
$category = $objectManager->create(Category::class);
$category->load(8);
if ($category->getId()) {
    $categoryRepository->delete($category);
}
/** @var UrlRewriteCollection $urlRewriteCollection */
$urlRewriteCollection = Bootstrap::getObjectManager()
    ->create(UrlRewriteCollection::class);

//remove the url from url rewrite table
$collection = $urlRewriteCollection
    ->addFieldToFilter('entity_type', 'product')
    ->addFieldToFilter('request_path', ['like' => '%simple-product-eight%'])
    ->load()
    ->walk('delete');

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
