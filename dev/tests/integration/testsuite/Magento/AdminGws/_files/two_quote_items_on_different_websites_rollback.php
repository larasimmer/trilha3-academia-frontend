<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Quote\Model\Quote;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

$registry = $objectManager->get(\Magento\Framework\Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var \Magento\Quote\Model\ResourceModel\Quote\Collection $quoteCollection */
$quoteCollection = $objectManager->create(\Magento\Quote\Model\ResourceModel\Quote\Collection::class);
$quoteCollection->load();
/** @var Quote $quote */
foreach ($quoteCollection->getItems() as $quote) {
    if (in_array($quote->getReservedOrderId(), ['test_order_item_1', 'test_order_item_2'])) {
        $quote->delete();
    }
}

/** @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->create(\Magento\Catalog\Api\ProductRepositoryInterface::class);
foreach (['simple_one', 'simple_two'] as $sku) {
    try {
        $product = $productRepository->get($sku, false, null, true);
    } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
        continue;
    }
    $productRepository->delete($product);
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);

Resolver::getInstance()->requireDataFixture('Magento/AdminGws/_files/two_roles_for_different_websites_rollback.php');
