<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\Website;
use Magento\Store\Model\Store;
use Magento\Customer\Model\Customer;
use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Catalog\Api\ProductRepositoryInterface;

$objectManager = Bootstrap::getObjectManager();
$storeRepository = $objectManager->get(StoreRepositoryInterface::class);

$registry = $objectManager->get(Registry::class);

/**
 * Marks area as secure so Product repository would allow product removal
 */
$isSecuredAreaSystemState = $registry->registry('isSecuredArea');
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/**
 * @var \Magento\Store\Model\Store $store
 */
$store = $storeRepository->get('second_store_view');
$storeGroupId = $store->getStoreGroupId();
$websiteId = $store->getWebsiteId();

/** @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->create(ProductRepositoryInterface::class);
$productRepository->deleteById('simple_product_second');

/** @var Magento\Store\Model\Store $store */
$store = $objectManager->create(Store::class);
$store->load('second_store_view');

if ($store->getId()) {
    $store->delete();
}

/**
 * remove website by id
 */
/** @var \Magento\Store\Model\Website $website */
$website = Bootstrap::getObjectManager()->create(Website::class);
$website->load((int)$websiteId);
$website->delete();

/** @var $customer \Magento\Customer\Model\Customer*/
$customer = Bootstrap::getObjectManager()->create(
    Customer::class
);
$customer->load(1);
if ($customer->getId()) {
    $customer->delete();
}

/**
 * Revert mark area secured
 */
$registry->unregister('isSecuredArea');
$registry->register('isSecuredArea', $isSecuredAreaSystemState);
