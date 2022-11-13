<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MultipleWishlist\Controller\Index;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Message\MessageInterface;
use Magento\TestFramework\MultipleWishlist\Model\GetCustomerWishListByName;
use Magento\TestFramework\TestCase\AbstractController;
use Magento\TestFramework\Wishlist\Model\GetWishlistByCustomerId;

/**
 * Test for move wish list item.
 *
 * @magentoDbIsolation disabled
 * @magentoAppArea frontend
 */
class MoveitemTest extends AbstractController
{
    /** @var Session */
    private $customerSession;

    /** @var GetWishlistByCustomerId */
    private $getWishlistByCustomerId;

    /** @var GetCustomerWishListByName */
    private $getCustomerWishListByName;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->customerSession = $this->_objectManager->get(Session::class);
        $this->getWishlistByCustomerId = $this->_objectManager->get(GetWishlistByCustomerId::class);
        $this->getCustomerWishListByName = $this->_objectManager->get(GetCustomerWishListByName::class);
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
     * @magentoConfigFixture current_store wishlist/general/multiple_enabled 1
     * @magentoDataFixture Magento/MultipleWishlist/_files/wishlists_with_two_items.php
     * @magentoDbIsolation disabled
     *
     * @return void
     */
    public function testMoveWishListItem(): void
    {
        $customerId = 1;
        $this->customerSession->loginById($customerId);
        $firstWishList = $this->getCustomerWishListByName->execute($customerId, 'First Wish List');
        $secondWishList = $this->getCustomerWishListByName->execute($customerId, 'Second Wish List');
        $item = $firstWishList->getItemCollection()->getFirstItem();
        $this->assertNotNull($item->getId());
        $params = ['wishlist_id' => $secondWishList->getWishlistId(), 'item_id' => $item->getId()];
        $this->performMoveItemRequest($params);
        $message = sprintf('"%s" was moved to %s.', $item->getProduct()->getName(), $secondWishList->getName());
        $this->assertSessionMessages($this->equalTo([(string)__($message)]), MessageInterface::TYPE_SUCCESS);
        $this->assertCount(
            1,
            $this->getCustomerWishListByName->execute($customerId, $firstWishList->getName())->getItemCollection()
        );
        $this->assertCount(
            1,
            $this->getCustomerWishListByName->execute($customerId, $secondWishList->getName())->getItemCollection()
        );
    }

    /**
     * @magentoConfigFixture current_store wishlist/general/multiple_enabled 1
     * @magentoDataFixture Magento/Wishlist/_files/wishlist.php
     *
     * @return void
     */
    public function testMoveItemToParentWishList(): void
    {
        $this->customerSession->loginById(1);
        $wishList = $this->getWishlistByCustomerId->execute(1);
        $item = $this->getWishlistByCustomerId->getItemBySku(1, 'simple');
        $this->assertNotNull($item);
        $params = ['wishlist_id' => $wishList->getWishlistId(), 'item_id' => $item->getId()];
        $this->performMoveItemRequest($params);
        $message = sprintf('"%s" is already present in %s.', $item->getProduct()->getName(), $wishList->getName());
        $this->assertSessionMessages($this->equalTo([(string)__($message)]), MessageInterface::TYPE_ERROR);
    }

    /**
     * @magentoConfigFixture current_store wishlist/general/multiple_enabled 1
     * @magentoDataFixture Magento/Wishlist/_files/two_wishlists_for_two_diff_customers.php
     *
     * @return void
     */
    public function testMoveItemWhichNotRelevantToCustomer(): void
    {
        $this->customerSession->loginById(1);
        $wishList = $this->getWishlistByCustomerId->execute(1);
        $item = $this->getWishlistByCustomerId->getItemBySku(2, 'simple');
        $this->assertNotNull($item);
        $params = ['wishlist_id' => $wishList->getWishlistId(), 'item_id' => $item->getId()];
        $this->performMoveItemRequest($params);
        $this->assertSessionMessages(
            $this->equalTo([(string)__('We cannot move "%1".', $item->getProduct()->getName())]),
            MessageInterface::TYPE_ERROR
        );
    }

    /**
     * @magentoConfigFixture current_store wishlist/general/multiple_enabled 1
     * @magentoDataFixture Magento/Customer/_files/customer.php
     *
     * @return void
     */
    public function testMoveWishListItemWithoutItemId(): void
    {
        $this->customerSession->loginById(1);
        $params = ['wishlist_id' => 989];
        $this->performMoveItemRequest($params);
        $this->assert404NotFound();
    }

    /**
     * Perform move wish list item request.
     *
     * @param array $params
     * @return void
     */
    private function performMoveItemRequest(array $params): void
    {
        $this->getRequest()->setParams($params)->setMethod(HttpRequest::METHOD_POST);
        $this->dispatch('wishlist/index/moveitem');
    }
}
