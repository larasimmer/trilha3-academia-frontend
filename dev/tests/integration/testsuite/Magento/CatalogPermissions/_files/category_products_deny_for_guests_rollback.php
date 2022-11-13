<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\TestFramework\Helper\Bootstrap;

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
$productRepository = $objectManager->create(\Magento\Catalog\Api\ProductRepositoryInterface::class);

/** @var \Magento\Framework\Registry $registry */
$registry = $objectManager->get(\Magento\Framework\Registry::class);

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

$product = $productRepository->get('simple_deny_122', false, null, true);
if ($product->getId()) {
    $productRepository->delete($product);
}

/** @var $category \Magento\Catalog\Model\Category */
$category = $objectManager->create(\Magento\Catalog\Model\Category::class);
$category->load(4);
if ($category->getId()) {
    $category->delete();
}

$bootstrap = \Magento\TestFramework\Helper\Bootstrap::getInstance();
$bootstrap->reinitialize();

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);

/**
 * Reindex
 */
$appDir = dirname(Bootstrap::getInstance()->getAppTempDir());
$out = '';
// phpcs:ignore Magento2.Security.InsecureFunction
exec("php -f {$appDir}/bin/magento indexer:reindex catalogpermissions_category", $out);
