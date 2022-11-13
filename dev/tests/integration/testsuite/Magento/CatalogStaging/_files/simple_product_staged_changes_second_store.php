<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\CatalogStaging\Api\ProductStagingInterface;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\UpdateFactory;
use Magento\Staging\Model\VersionManager;
use Magento\Store\Model\Store;
use Magento\Store\Model\Group;
use \Magento\Store\Model\Website;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
$updateFactory = $objectManager->get(UpdateFactory::class);
$updateRepository = $objectManager->get(UpdateRepositoryInterface::class);
$productStaging = $objectManager->get(ProductStagingInterface::class);
$versionManager = $objectManager->get(VersionManager::class);
$productRepository = $objectManager->get(ProductRepositoryInterface::class);

/**
 * Create Website
 */

/**
 * @var Website $website
 */
$website = $objectManager->get(Magento\Store\Model\Website::class);
$website->load('test_secondwebsite', 'code');

if (!$website->getId()) {
    /** @var Magento\Store\Model\Website $website */
    $website->setData(
        [
            'code' => 'test_secondwebsite',
            'name' => 'test second website',

        ]
    );

    $website->save();
}

/**
 * Create store group
 */

/**
 * @var Group $storeGroup
 */
$storeGroup = $objectManager->create(Group::class);
$storeGroup->setCode('secondstorecode')
    ->setName('second store')
    ->setRootCategoryId(2)
    ->setWebsite($website);

$storeGroup->save($storeGroup);

$website->setDefaultGroupId($storeGroup->getId());
$website->save($website);

$websiteId = $website->getId();

//Create Stores
/** @var Store $store */
$store = $objectManager->create(Store::class);
$store->load('fixture_second_store', 'code');

if (!$store->getId()) {
    $groupId = $website->getDefaultGroupId();
    $store->setData(
        [
            'code' => 'fixture_second_store',
            'website_id' => $websiteId,
            'group_id' => $groupId,
            'name' => 'Fixture Second Store',
            'sort_order' => 10,
            'is_active' => 1,
        ]
    );
    $store->save();
}

//create product
/** @var Product $product */
$product = $objectManager->create(Product::class);
$product->setTypeId(Type::TYPE_SIMPLE)
    ->setAttributeSetId(4)
    ->setWebsiteIds([1, $website->getId()])
    ->setName('Simple Product 1')
    ->setSku('simplep1')
    ->setPrice(50)
    ->setQty(100)
    ->setUrlKey('simple1-' . rand(10, 1000))
    ->setDescription('Description with <b>html tag</b>')
    ->setVisibility(Visibility::VISIBILITY_BOTH)
    ->setStatus(Status::STATUS_ENABLED)
    ->setStockData(['use_config_manage_stock' => 0])
    ->setCanSaveCustomOptions(true)
    ->setHasOptions(true);
$productRepository->save($product);

/** @var \Magento\Store\Model\Store $store */
$store =  $objectManager->get(\Magento\Store\Model\Store::class);
$storeCodeFromFixture = 'fixture_second_store';
$storeId = $store->load($storeCodeFromFixture)->getStoreId();

//Stage changes
$startTime = date('Y-m-d H:i:s', strtotime('+1 day'));
$endTime = date('Y-m-d H:i:s', strtotime('+10 days'));
$updateData = [
    'name' => 'Product Update Second Store',
    'start_time' => $startTime,
    'end_time' => $endTime,
    'is_campaign' => 0,
    'is_rollback' => null,
];

$update = $updateFactory->create(['data' => $updateData]);
$updateRepository->save($update);
/** @var \Magento\Store\Model\StoreManagerInterface $storeManageInterface */
$storeManageInterface = $objectManager->get(\Magento\Store\Model\StoreManagerInterface::class);
$storeManageInterface->setCurrentStore($storeId);

$product = $productRepository->get('simplep1');

$versionManager->setCurrentVersionId($update->getId());

$product->setName('Updated Product Name store2')->setPrice(40)->save();
$productStaging->schedule($product, $update->getId());
