<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Wishlist\Model\ResourceModel\Wishlist;
use Magento\Wishlist\Model\WishlistFactory;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;
use Magento\Customer\Model\CustomerRegistry;

Resolver::getInstance()->requireDataFixture('Magento/Customer/_files/two_customers.php');
Resolver::getInstance()->requireDataFixture('Magento/Catalog/_files/product_simple_duplicated.php');
Resolver::getInstance()->requireDataFixture('Magento/Catalog/_files/second_product_simple.php');

$objectManager = Bootstrap::getObjectManager();
/** @var CustomerRegistry $customerRegistry */
$customerRegistry = Bootstrap::getObjectManager()->create(CustomerRegistry::class);
$customer = $customerRegistry->retrieve(1);
/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->create(ProductRepositoryInterface::class);
$product = $productRepository->get('simple-1');
$product2 = $productRepository->get('simple2');
/** @var WishlistFactory $wishlistFactory */
$wishlistFactory = $objectManager->get(WishlistFactory::class);
/** @var Wishlist $wishlistResource */
$wishlistResource = $objectManager->get(Wishlist::class);

$firstWishlist = $wishlistFactory->create();
$firstWishlist->setCustomerId($customer->getId())
    ->setName('First Wish List')
    ->setVisibility(1);
$wishlistResource->save($firstWishlist);
$firstWishlist->addNewItem($product);
$firstWishlist->addNewItem($product2);

$secondWishlist = $wishlistFactory->create();
$secondWishlist->setCustomerId($customer->getId())
    ->setName('Second Wish List');
$wishlistResource->save($secondWishlist);
