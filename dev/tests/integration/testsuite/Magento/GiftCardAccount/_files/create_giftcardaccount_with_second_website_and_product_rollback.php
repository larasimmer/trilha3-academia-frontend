<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

use \Magento\Framework\App\ObjectManager;
use \Magento\Store\Api\StoreRepositoryInterface;
use \Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use \Magento\Framework\Registry;
use \Magento\TestFramework\Helper\Bootstrap;

/**
 * Roll back fixtures
 *  - Remove Product
 *  - Remove Website/StoreGroup/[Store1, Store2]
 *  - ReIndex Full text indexers
 */

$objectManager = Bootstrap::getObjectManager();
$storeRepository = $objectManager->get(StoreRepositoryInterface::class);
$resourceConnection = $objectManager->get(ResourceConnection::class);

/**
 * @var AdapterInterface $connection
 */
$connection = $resourceConnection->getConnection();
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
$store = $storeRepository->get('fixture_second_store');
$storeGroupId = $store->getStoreGroupId();
$websiteId = $store->getWebsiteId();


/** @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->create(\Magento\Catalog\Api\ProductRepositoryInterface::class);
$product = $productRepository->get('simple_two', false, null, true);
$productRepository->delete($product);

/**
 * Remove stores by code
 */
$storeCodes = [
    'custom store'
];

$connection->delete(
    $resourceConnection->getTableName('store'),
    [
        'code IN (?)' => $storeCodes,
    ]
);

/**
 * removeStoreGroupById
 */
$connection->delete(
    $resourceConnection->getTableName('store_group'),
    [
        'group_id = ?' => $storeGroupId,
    ]
);

/**
 * remove website by id
 */
/** @var \Magento\Store\Model\Website $website */
$website = Bootstrap::getObjectManager()->create(\Magento\Store\Model\Website::class);
$website->load((int)$websiteId);
$website->delete();

/**
 * reIndex all
 */
ObjectManager::getInstance()
    ->create(\Magento\CatalogSearch\Model\Indexer\Fulltext\Processor::class)
    ->reindexAll();

/**
 * Revert mark area secured
 */
$registry->unregister('isSecuredArea');
$registry->register('isSecuredArea', $isSecuredAreaSystemState);

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
/** @var \Magento\GiftCardAccount\Api\GiftCardAccountRepositoryInterface $repo */
$repo = $objectManager->create(\Magento\GiftCardAccount\Api\GiftCardAccountRepositoryInterface::class);
/** @var \Magento\Framework\Api\SearchCriteriaBuilder $criteriaBuilder */
$criteriaBuilder = $objectManager->get(\Magento\Framework\Api\SearchCriteriaBuilder::class);
$accounts = $repo->getList(
    $criteriaBuilder->addFilter('code', 'gift_card_account_two')->setPageSize(1)->create()
)->getItems();
$account = array_pop($accounts);
if ($account) {
    $repo->delete($account);
}
