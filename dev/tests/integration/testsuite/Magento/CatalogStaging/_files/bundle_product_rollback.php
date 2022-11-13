<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

/** @var \Magento\Framework\Registry $registry */
$registry = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(\Magento\Framework\Registry::class);

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepository */
$productRepository = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
    \Magento\Catalog\Api\ProductRepositoryInterface::class
);
try {
    $product = $productRepository->get('bundle-product', false, null, true);
    $productRepository->delete($product);

    $urlRewrite = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
        \Magento\UrlRewrite\Model\UrlRewrite::class
    );
    $urlRewrite->load('bundle-product.html', 'request_path');
    $urlRewrite->delete();

} catch (\Magento\Framework\Exception\NoSuchEntityException $exception) {
    // product does not exist or already removed
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);

Resolver::getInstance()->requireDataFixture('Magento/Store/_files/core_fixturestore_rollback.php');
Resolver::getInstance()->requireDataFixture('Magento/CatalogStaging/_files/simple_product_rollback.php');
