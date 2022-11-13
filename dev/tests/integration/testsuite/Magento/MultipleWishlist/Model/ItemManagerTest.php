<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MultipleWishlist\Model;

use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\MultipleWishlist\Model\GetCustomerWishListByName;
use Magento\TestFramework\Wishlist\Model\GetWishlistByCustomerId;
use Magento\Wishlist\Model\ResourceModel\Wishlist\CollectionFactory;
use Magento\Wishlist\Model\WishlistFactory;
use PHPUnit\Framework\TestCase;

/**
 * Tests for wish list item manager model.
 *
 * @magentoDbIsolation disabled
 * @magentoAppIsolation disabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ItemManagerTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var ItemManagerFactory */
    private $itemManagerFactory;

    /** @var ItemFactory */
    private $itemFactory;

    /** @var WishlistFactory */
    private $wishlistFactory;

    /** @var GetCustomerWishListByName */
    private $getCustomerWishListByName;

    /** @var CollectionFactory */
    private $wishlistCollectionFactory;

    /** @var GetWishlistByCustomerId */
    private $getWishlistByCustomerId;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->itemManagerFactory = $this->objectManager->get(ItemManagerFactory::class);
        $this->itemFactory = $this->objectManager->get(ItemFactory::class);
        $this->wishlistFactory = $this->objectManager->get(WishlistFactory::class);
        $this->getCustomerWishListByName = $this->objectManager->get(GetCustomerWishListByName::class);
        $this->wishlistCollectionFactory = $this->objectManager->get(CollectionFactory::class);
        $this->getWishlistByCustomerId = $this->objectManager->get(GetWishlistByCustomerId::class);
    }

    /**
     * @magentoDataFixture Magento/MultipleWishlist/_files/wishlists_with_two_items.php
     * @magentoDbIsolation disabled
     *
     * @return void
     */
    public function testCopyItemToSecondWishList(): void
    {
        $customerId = 1;
        $firstWishList = $this->getCustomerWishListByName->execute($customerId, 'First Wish List');
        $secondWishList = $this->getCustomerWishListByName->execute($customerId, 'Second Wish List');
        $item = $firstWishList->getItemCollection()->getFirstItem();
        $this->assertNotNull($item);
        $this->itemManagerFactory->create()->copy($item, $secondWishList);
        $this->assertItemsCount($customerId, ['First Wish List' => 2, 'Second Wish List' => 1]);
    }

    /**
     * @return void
     */
    public function testCopyNotExistingItem(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->itemManagerFactory->create()->copy($this->itemFactory->create(), $this->wishlistFactory->create());
    }

    /**
     * @magentoDataFixture Magento/Wishlist/_files/wishlist.php
     *
     * @return void
     */
    public function testCopyItemToParentWishList(): void
    {
        $wishList = $this->getWishlistByCustomerId->execute(1);
        $item = $this->getWishlistByCustomerId->getItemBySku(1, 'simple');
        $this->expectException(\DomainException::class);
        $this->itemManagerFactory->create()->copy($item, $wishList);
    }

    /**
     * @magentoDataFixture Magento/MultipleWishlist/_files/wishlists_with_two_items.php
     * @magentoDbIsolation disabled
     *
     * @return void
     */
    public function testMoveItemToSecondWishList(): void
    {
        $customerId = 1;
        $firstWishList = $this->getCustomerWishListByName->execute($customerId, 'First Wish List');
        $secondWishList = $this->getCustomerWishListByName->execute($customerId, 'Second Wish List');
        $item = $firstWishList->getItemCollection()->getFirstItem();
        $this->assertNotNull($item->getId());
        $wishListCollection = $this->wishlistCollectionFactory->create()->filterByCustomerId($customerId);
        $this->itemManagerFactory->create()->move($item, $secondWishList, $wishListCollection);
        $this->assertItemsCount($customerId, ['First Wish List' => 1, 'Second Wish List' => 1]);
    }

    /**
     * @return void
     */
    public function testMoveNotExistingItem(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->itemManagerFactory->create()->move(
            $this->itemFactory->create(),
            $this->wishlistFactory->create(),
            $this->wishlistCollectionFactory->create()
        );
    }

    /**
     * @magentoDataFixture Magento/Wishlist/_files/wishlist.php
     *
     * @return void
     */
    public function testMoveItemToParentWishList(): void
    {
        $wishList = $this->getWishlistByCustomerId->execute(1);
        $item = $this->getWishlistByCustomerId->getItemBySku(1, 'simple');
        $wishListCollection = $this->wishlistCollectionFactory->create()->filterByCustomerId(1);
        $this->expectException(\DomainException::class);
        $this->itemManagerFactory->create()->move($item, $wishList, $wishListCollection);
    }

    /**
     * @magentoDataFixture Magento/MultipleWishlist/_files/wishlists_with_two_items.php
     * @magentoDbIsolation disabled
     *
     * @return void
     */
    public function testMoveItemToNotExistingWishList(): void
    {
        $customerId = 1;
        $firstWishList = $this->getCustomerWishListByName->execute($customerId, 'First Wish List');
        $secondWishList = $this->getCustomerWishListByName->execute($customerId, 'Second Wish List');
        $item = $firstWishList->getItemCollection()->getFirstItem();
        $this->assertNotNull($item->getId());
        $item->setWishlistId(989);
        $wishListCollection = $this->wishlistCollectionFactory->create()->filterByCustomerId(1);
        $this->expectException(\DomainException::class);
        $this->itemManagerFactory->create()->move($item, $secondWishList, $wishListCollection);
    }

    /**
     * Assert items count in wish lists.
     *
     * @param int $customerId
     * @param array $expectedData
     * @return void
     */
    private function assertItemsCount(int $customerId, array $expectedData): void
    {
        foreach ($expectedData as $wishListName => $itemsCount) {
            $this->assertCount(
                $itemsCount,
                $this->getCustomerWishListByName->execute($customerId, $wishListName)->getItemCollection()
            );
        }
    }
}
