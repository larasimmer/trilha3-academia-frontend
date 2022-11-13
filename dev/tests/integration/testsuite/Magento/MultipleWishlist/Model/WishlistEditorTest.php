<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MultipleWishlist\Model;

use Magento\Customer\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\MultipleWishlist\Model\GetCustomerWishListByName;
use Magento\TestFramework\Wishlist\Model\GetWishlistByCustomerId;
use PHPUnit\Framework\TestCase;

/**
 * Tests for wish list editor model.
 *
 * @magentoDbIsolation enabled
 * @magentoAppIsolation disabled
 */
class WishlistEditorTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var WishlistEditorFactory */
    private $wishlistEditorFactory;

    /** @var GetWishlistByCustomerId */
    private $getWishlistByCustomerId;

    /** @var Session */
    private $customerSession;

    /** @var GetCustomerWishListByName */
    private $getCustomerWishListByName;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->wishlistEditorFactory = $this->objectManager->get(WishlistEditorFactory::class);
        $this->getWishlistByCustomerId = $this->objectManager->get(GetWishlistByCustomerId::class);
        $this->customerSession = $this->objectManager->get(Session::class);
        $this->getCustomerWishListByName = $this->objectManager->get(GetCustomerWishListByName::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        $this->customerSession->logout();

        parent::tearDown();
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     *
     * @return void
     */
    public function testCreateWishList(): void
    {
        $wishListName = 'New Wish List';
        $createdWishList = $this->wishlistEditorFactory->create()->edit(1, $wishListName, true);
        $this->assertEquals($wishListName, $createdWishList->getName());
        $this->assertEquals(1, $createdWishList->getVisibility());
    }

    /**
     * @magentoDataFixture Magento/Wishlist/_files/wishlist.php
     *
     * @return void
     */
    public function testEditWishList(): void
    {
        $customerId = 1;
        $this->customerSession->loginById($customerId);
        $wishListForUpdate = $this->getWishlistByCustomerId->execute(1);
        $wishListName = 'New Wish List Name';
        $wishListEditor = $this->wishlistEditorFactory->create();
        $updatedWishList = $wishListEditor->edit($customerId, $wishListName, true, $wishListForUpdate->getWishlistId());
        $this->assertNotNull($updatedWishList);
        $this->assertEquals($wishListName, $updatedWishList->getName());
        $this->assertEquals(1, $updatedWishList->getVisibility());
    }

    /**
     * @magentoDataFixture Magento/Wishlist/_files/two_wishlists_for_two_diff_customers.php
     *
     * @return void
     */
    public function testEditWishListByCustomerWhoNotOwn(): void
    {
        $this->customerSession->loginById(2);
        $customerId = 1;
        $wishList = $this->getWishlistByCustomerId->execute(1);
        $this->expectExceptionObject(
            new LocalizedException(__('The wish list is not assigned to your account and can\'t be edited.'))
        );
        $this->wishlistEditorFactory->create()->edit($customerId, 'New Wish List', false, $wishList->getWishlistId());
    }

    /**
     * @return void
     */
    public function testCreateWishListWithInvalidCustomerId(): void
    {
        $this->expectExceptionObject(new LocalizedException(__('Sign in to edit wish lists.')));
        $this->wishlistEditorFactory->create()->edit(null, 'New Wish List');
    }

    /**
     * @return void
     */
    public function testCreateWishListWithoutName(): void
    {
        $this->expectExceptionObject(new LocalizedException(__('Provide the wish list name.')));
        $this->wishlistEditorFactory->create()->edit(1, '');
    }

    /**
     * @magentoDataFixture Magento/MultipleWishlist/_files/wishlists_with_two_items.php
     *
     * @return void
     */
    public function testCreateWishListWithAlreadyExistingName(): void
    {
        $name = 'First Wish List';
        $this->expectExceptionObject(new LocalizedException(__('Wish list "%1" already exists.', $name)));
        $this->wishlistEditorFactory->create()->edit(1, $name);
    }

    /**
     * @magentoDataFixture Magento/MultipleWishlist/_files/wishlists_with_two_items.php
     *
     * @return void
     */
    public function testEditWishListWithAlreadyExistingName(): void
    {
        $this->markTestSkipped('Test is blocked by issue MC-30278');
        $customerId = 1;
        $this->customerSession->loginById($customerId);
        $wishList = $this->getCustomerWishListByName->execute($customerId, 'Second Wish List');
        $name = 'First Wish List';
        $this->expectExceptionObject(new LocalizedException(__('Wish list "%1" already exists.', $name)));
        $this->wishlistEditorFactory->create()->edit($customerId, $name, false, $wishList->getWishlistId());
    }

    /**
     * @magentoConfigFixture current_store wishlist/general/multiple_wishlist_number 2
     * @magentoDataFixture Magento/Customer/_files/customer.php
     *
     * @return void
     */
    public function testLimitCustomerWishLists(): void
    {
        $customerId = 1;
        $this->expectExceptionObject(new LocalizedException(__('Only %1 wish list(s) can be created.', 2)));

        foreach (range(1, 5) as $number) {
            $this->wishlistEditorFactory->create()->edit($customerId, sprintf('New Wish List %s', $number));
        }
    }
}
