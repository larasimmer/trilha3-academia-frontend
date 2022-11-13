<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MultipleWishlist\Helper\Data;

use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\MultipleWishlist\Helper\Data;
use PHPUnit\Framework\TestCase;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Wishlist\Model\WishlistFactory;

/**
 * @magentoAppIsolation enabled
 * @magentoAppArea adminhtml
 */
class DataTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var Data */
    private $wishListHelper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->wishListHelper = $this->objectManager->get(Data::class);
    }

    /**
     * Test get wishlist item count with in stock product and use qty disabled
     *
     * @magentoDataFixture Magento/Checkout/_files/quote_with_customer_without_address.php
     * @magentoDataFixture Magento/MultipleWishlist/_files/wishlists_with_two_items.php
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     */
    public function testGetWishlistItemCountWithUseQtyDisabled(): void
    {
        $customerId = 1;
        $wishlistFactory = $this->objectManager->get(WishlistFactory::class);
        $wishlist = $wishlistFactory->create()->loadByCustomerId($customerId, false);
        $noOfWishlistItems = $this->wishListHelper->getWishlistItemCount($wishlist);
        $this->assertEquals(2, $noOfWishlistItems);
        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $this->objectManager->create(ProductRepositoryInterface::class);
        $newSimpleProduct = $productRepository->get('simple');
        $wishlist->addNewItem($newSimpleProduct);

        $wishlist = $wishlistFactory->create()->loadByCustomerId($customerId, false);
        $noOfWishlistItems = $this->wishListHelper->getWishlistItemCount($wishlist);
        $this->assertEquals(3, $noOfWishlistItems);
    }

    /**
     * Test get wishlist item count with out of stock product and use qty disabled
     *
     * @magentoDataFixture Magento/Wishlist/_files/wishlist_with_disabled_product.php
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     */
    public function testGetWishlistItemCountWithOutOfStockProduct(): void
    {
        $customerId = 1;
        $wishlistFactory = $this->objectManager->get(WishlistFactory::class);
        $wishlist = $wishlistFactory->create()->loadByCustomerId($customerId, false);
        $noOfWishlistItems = $this->wishListHelper->getWishlistItemCount($wishlist);
        $this->assertEquals(0, $noOfWishlistItems);
        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $this->objectManager->create(ProductRepositoryInterface::class);
        $newSimpleProduct = $productRepository->get('simple');
        $wishlist->addNewItem($newSimpleProduct);

        $wishlist = $wishlistFactory->create()->loadByCustomerId($customerId, false);
        $noOfWishlistItems = $this->wishListHelper->getWishlistItemCount($wishlist);
        $this->assertEquals(1, $noOfWishlistItems);
    }

    /**
     * Test get wishlist item count with use qty enabled in config
     *
     * @magentoDataFixture Magento/Checkout/_files/quote_with_customer_without_address.php
     * @magentoDataFixture Magento/MultipleWishlist/_files/wishlists_with_two_items.php
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoConfigFixture wishlist/wishlist_link/use_qty 1
     */
    public function testGetWishlistItemCountWithUseQtyEnabled(): void
    {
        $customerId = 1;
        $wishlistFactory = $this->objectManager->get(WishlistFactory::class);
        $wishlist = $wishlistFactory->create()->loadByCustomerId($customerId, false);
        $noOfWishlistItems = $this->wishListHelper->getWishlistItemCount($wishlist);
        $this->assertEquals(2, $noOfWishlistItems);
    }
}
