<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

use Magento\TestFramework\Helper\Bootstrap;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Store\Model\Store;
use Magento\Store\Model\Group;
use Magento\Store\Model\Website;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Store\Model\StoreManagerInterface;
use Magento\CustomerBalance\Model\Balance;
use Magento\Customer\Model\Customer;
use Magento\Framework\Api\DataObjectHelper;

$objectManager = Bootstrap::getObjectManager();

$defaultWebsiteId = $objectManager->get(
    StoreManagerInterface::class
)->getStore()->getWebsiteId();

/**
 * @var Website $website
 */
$website2 = $objectManager->get(Website::class);
$website2->load('second_website', 'code');

if (!$website2->getId()) {
    /** @var Magento\Store\Model\Website $website */
    $website2->setData(
        [
            'code' => 'second_website',
            'name' => 'Second Website',

        ]
    );

    $website2->save();
}

 // Create store group

/**
 * @var Group $storeGroup
 */
$storeGroup = $objectManager->create(Group::class);
$storeGroup->setCode('second_store')
    ->setName('Second Store')
    ->setWebsite($website2);
$storeGroup->save();

$website2->setDefaultGroupId($storeGroup->getId());
$website2->save();

$websiteId2 = $website2->getId();

//Create Stores

/**
 * @var Store $store
 */
$store = $objectManager->create(Store::class);
$store->load('second_store_view', 'code');

if (!$store->getId()) {
    $groupId = $website2->getDefaultGroupId();
    $store->setData(
        [
            'code' => 'second_store_view',
            'website_id' => $websiteId2,
            'group_id' => $groupId,
            'name' => 'Fixture Second Store',
            'sort_order' => 10,
            'is_active' => 1,
        ]
    );
    $store->save();
}

/** @var ProductInterfaceFactory $productFactory */
$productFactory = $objectManager->get(ProductInterfaceFactory::class);
/** @var DataObjectHelper $dataObjectHelper */
$dataObjectHelper = $objectManager->get(DataObjectHelper::class);
/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);

$product = $productFactory->create();
$productData = [
    ProductInterface::TYPE_ID => Type::TYPE_SIMPLE,
    ProductInterface::ATTRIBUTE_SET_ID => 4,
    ProductInterface::SKU => 'simple_product_second',
    ProductInterface::NAME => 'Second Simple Product',
    ProductInterface::PRICE => 20,
    ProductInterface::VISIBILITY => Visibility::VISIBILITY_BOTH,
    ProductInterface::STATUS => Status::STATUS_ENABLED,
];
$dataObjectHelper->populateWithArray($product, $productData, ProductInterface::class);
$product
    ->setWebsiteIds([$website2->getId()])
    ->setStockData(
        [
        'qty' => 85,
        'is_in_stock' => true,
        'manage_stock' => true,
        'is_qty_decimal' => true
        ]
    );
$productRepository->save($product);

$customer = $objectManager->create(Customer::class);
/** @var CustomerRegistry $customerRegistry */
$customerRegistry = $objectManager->get(CustomerRegistry::class);
/** @var Magento\Customer\Model\Customer $customer */
$customer->setWebsiteId(1)
    ->setId(1)
    ->setEmail('customer@example.com')
    ->setPassword('password')
    ->setGroupId(1)
    ->setStoreId(1)
    ->setIsActive(1)
    ->setPrefix('Mr.')
    ->setFirstname('John')
    ->setMiddlename('A')
    ->setLastname('Smith')
    ->setSuffix('Esq.')
    ->setDefaultBilling(1)
    ->setDefaultShipping(1)
    ->setTaxvat('12')
    ->setGender(0);

$customer->isObjectNew(true);
$customer->save();
$customerRegistry->remove($customer->getId());

/** @var $customerBalance Magento\CustomerBalance\Model\Balance */
$customerBalance = $objectManager->create(
    Balance::class
);
$customerBalance->setCustomerId(
    $customer->getId()
);
$customerBalance->setAmountDelta(50);
$customerBalance->setWebsiteId($defaultWebsiteId);
$customerBalance->save();

/** @var $customerBalance Magento\CustomerBalance\Model\Balance */
$customerBalance = $objectManager->create(
    Balance::class
);
$customerBalance->setCustomerId(
    $customer->getId()
);
$customerBalance->setAmountDelta(150);
$customerBalance->setWebsiteId($website2->getId());
$customerBalance->save();
