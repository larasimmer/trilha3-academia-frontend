<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\ResourceModel\Quote\Collection;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

$objectManager = Bootstrap::getObjectManager();

$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var Collection $quoteCollection */
$quoteCollection = $objectManager->create(Collection::class);
$quoteCollection->load();
/** @var Quote $quote */
foreach ($quoteCollection->getItems() as $quote) {
    if (in_array($quote->getReservedOrderId(), ['test_order_item_1'])) {
        $quote->delete();
    }
}

/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->create(ProductRepositoryInterface::class);
$product = $productRepository->get('simple_report', false, null, true);
try {
    $productRepository->delete($product);
} catch (NoSuchEntityException $e) {
    // Product already deleted
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);

Resolver::getInstance()
    ->requireDataFixture('Magento/AdminGws/_files/two_roles_for_different_websites_rollback.php');
