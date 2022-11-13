<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

$registry = $objectManager->get(\Magento\Framework\Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

$productRepository = $objectManager->create(\Magento\Catalog\Api\ProductRepositoryInterface::class);
for ($i = 1; $i <= 2; $i++) {
    try {
        $product = $productRepository->get('simple' . $i, false, null, true);
    } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
        continue;
    }
    $productRepository->delete($product);
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
