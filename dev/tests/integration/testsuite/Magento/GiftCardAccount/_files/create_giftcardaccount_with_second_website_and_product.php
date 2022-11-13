<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Store\Model\Store;
use Magento\Store\Model\Group;
use \Magento\Store\Model\Website;

/** @var $model \Magento\GiftCardAccount\Model\Giftcardaccount */
$model = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
    \Magento\GiftCardAccount\Model\Giftcardaccount::class
);

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

/**
 * Create Website
 */

/**
 * @var Website $website
 */
$website = $objectManager->get(Magento\Store\Model\Website::class);
$website->load('test_websiteNew', 'code');

if (!$website->getId()) {
    /** @var Magento\Store\Model\Website $website */
    $website->setData(
        [
            'code' => 'test_websiteNew',
            'name' => 'test websiteNew',

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
$storeGroup->setCode('someCode')
    ->setName('custom store')
    ->setWebsite($website);
$storeGroup->save($storeGroup);

$website->setDefaultGroupId($storeGroup->getId());
$website->save($website);

$websiteId = $website->getId();

/**
 * Create Stores
 */

/**
 * @var Store $store
 */
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

/** @var $product \Magento\Catalog\Model\Product */
$product = $objectManager->create(\Magento\Catalog\Model\Product::class);
$product->setTypeId('simple')
    ->setName('Simple Product Two')
    ->setSku('simple_two')
    ->setWebsiteIds([$website->getId()])
    ->setPrice(20)
    ->setStockData(['use_config_manage_stock' => 0])
    ->setAttributeSetId(4)
    ->setWeight(18)
    ->setStockData(['use_config_manage_stock' => 0])
    ->setCategoryIds([9])
    ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
    ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
    ->save();

$product->load(1)
    ->setStoreId($store->getId())
    ->setName('custom store')
    ->save();

$model->setCode(
    'gift_card_account_two'
)->setStatus(
    \Magento\GiftCardAccount\Model\Giftcardaccount::STATUS_ENABLED
)->setState(
    \Magento\GiftCardAccount\Model\Giftcardaccount::STATE_AVAILABLE
)->setWebsiteId(
    $websiteId
)->setIsRedeemable(
    \Magento\GiftCardAccount\Model\Giftcardaccount::REDEEMABLE
)->setBalance(
    9.99
)->setDateExpires(
    date('Y-m-d', strtotime('+1 week'))
)->save();
