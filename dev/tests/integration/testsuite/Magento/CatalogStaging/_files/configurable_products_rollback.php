<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Model\Stock\Status;
use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollection;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

$objectManager = Bootstrap::getObjectManager();

/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var ProductRepositoryInterface $productRepository */
$productRepository = Bootstrap::getObjectManager()
    ->get(ProductRepositoryInterface::class);

foreach (['simple_10', 'simple_20', 'configurable', 'simple_30', 'simple_40', 'configurable_12345'] as $sku) {
    try {
        $product = $productRepository->get($sku, true);
        $productRepository->delete($product);
    } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
        //Products already removed
    }
}

/** @var UrlRewriteCollection $urlRewriteCollection */
$urlRewriteCollection = Bootstrap::getObjectManager()
    ->create(UrlRewriteCollection::class);

//remove the url from url rewrite table
$collection = $urlRewriteCollection
    ->addFieldToFilter('entity_type', 'product')
    ->addFieldToFilter('request_path', ['like' => '%configurable%'])
    ->load()
    ->walk('delete');

Resolver::getInstance()->requireDataFixture('Magento/CatalogStaging/_files/configurable_attribute_rollback.php');

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
