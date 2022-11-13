<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
$productRepository = $objectManager->create(\Magento\Catalog\Api\ProductRepositoryInterface::class);

Resolver::getInstance()->requireDataFixture('Magento/CatalogPermissions/_files/category_rollback.php');

/** @var \Magento\Framework\Registry $registry */
$registry = $objectManager->get(\Magento\Framework\Registry::class);

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var $product \Magento\Catalog\Model\Product */
$product = $productRepository->get('simple_allow_122', false, null, true);
if ($product->getId()) {
    $productRepository->delete($product);
}
$product = $productRepository->get('simple_deny_122', false, null, true);
if ($product->getId()) {
    $productRepository->delete($product);
}

$bootstrap = \Magento\TestFramework\Helper\Bootstrap::getInstance();
$bootstrap->reinitialize();

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
