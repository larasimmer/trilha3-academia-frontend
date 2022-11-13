<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
use Magento\Customer\Model\CustomerRegistry;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/Customer/_files/two_customers.php');
Resolver::getInstance()->requireDataFixture('Magento/MultipleWishlist/_files/products.php');

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

$storeRepository = $objectManager->create(\Magento\Store\Api\StoreRepositoryInterface::class);
$store = $storeRepository->get('default');
/** @var CustomerRegistry $customerRegistry */
$customerRegistry = Bootstrap::getObjectManager()->create(CustomerRegistry::class);
$customer = $customerRegistry->retrieve(1);

$productRepository = $objectManager->create(\Magento\Catalog\Api\ProductRepositoryInterface::class);
for ($i = 1; $i <= 2; $i++) {
    $wishlist = $objectManager->create(\Magento\Wishlist\Model\Wishlist::class);
    $wishlist->setSharingCode('wishlist_fixture_' . $i)
        ->setName('Wish list ' . $i)
        ->setStore($store)
        ->setCustomerId($customer->getId());
    $wishlist->save();

    $product = $productRepository->get('simple' . $i);
    $wishlist->addNewItem($product, new \Magento\Framework\DataObject());
    $wishlist->save();
}
