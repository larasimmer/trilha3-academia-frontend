<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MultipleWishlist\Controller\Index;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Escaper;
use Magento\Framework\Message\MessageInterface;
use Magento\TestFramework\MultipleWishlist\Model\GetCustomerWishListByName;
use Magento\TestFramework\TestCase\AbstractController;
use Magento\TestFramework\Wishlist\Model\GetWishlistByCustomerId;
use Magento\Wishlist\Model\ResourceModel\Item\Collection;

/**
 * Test for move wish list items.
 *
 * @magentoDbIsolation disabled
 * @magentoAppArea frontend
 */
class MoveitemsTest extends AbstractController
{
    /** @var Session */
    private $customerSession;

    /** @var GetCustomerWishListByName */
    private $getCustomerWishListByName;

    /** @var GetWishlistByCustomerId */
    private $getWishlistByCustomerId;

    /** @var Escaper */
    private $escaper;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->customerSession = $this->_objectManager->get(Session::class);
        $this->getWishlistByCustomerId = $this->_objectManager->get(GetWishlistByCustomerId::class);
        $this->getCustomerWishListByName = $this->_objectManager->get(GetCustomerWishListByName::class);
        $this->escaper = $this->_objectManager->get(Escaper::class);
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
    public function testMoveWishListItems(): void
    {
        $customerId = 1;
        $this->customerSession->loginById($customerId);
        $firstWishList = $this->getCustomerWishListByName->execute($customerId, 'First Wish List');
        $secondWishList = $this->getCustomerWishListByName->execute($customerId, 'Second Wish List');
        $itemCollection = $firstWishList->getItemCollection();
        $itemsCount = $itemCollection->count();
        $itemsToRequest = $this->prepareItemsToRequest($itemCollection);
        $params = ['wishlist_id' => $secondWishList->getWishlistId(), 'selected' => $itemsToRequest['selected']];
        $this->performMoveItemsRequest($params);
        $message = $this->escaper->escapeHtml(
            sprintf(
                '%s items were moved to %s: %s.',
                $itemsCount,
                $secondWishList->getName(),
                implode(', ', $itemsToRequest['product_names'])
            )
        );
        $this->assertSessionMessages($this->equalTo([$message]), MessageInterface::TYPE_SUCCESS);
        $this->assertCount(
            0,
            $this->getCustomerWishListByName->execute($customerId, $firstWishList->getName())->getItemCollection()
        );
        $this->assertCount(
            $itemsCount,
            $this->getCustomerWishListByName->execute($customerId, $secondWishList->getName())->getItemCollection()
        );
    }

    /**
     * @magentoConfigFixture current_store wishlist/general/multiple_enabled 1
     * @magentoDataFixture Magento/Wishlist/_files/wishlist.php
     *
     * @return void
     */
    public function testMoveNotExistingWishListItems(): void
    {
        $this->customerSession->loginById(1);
        $wishList = $this->getWishlistByCustomerId->execute(1);
        $selected = [989 => 'on', 999 => 'on'];
        $params = ['wishlist_id' => $wishList->getWishlistId(), 'selected' => $selected];
        $this->performMoveItemsRequest($params);
        $this->assertSessionMessages(
            $this->equalTo([(string)__('We can\'t find %1 items.', count($selected))]),
            MessageInterface::TYPE_ERROR
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
        $params = ['wishlist_id' => $wishList->getWishlistId(), 'selected' => [$item->getId() => 'on']];
        $this->performMoveItemsRequest($params);
        $message = $this->escaper->escapeHtml(
            sprintf('1 items are already present in %s: "%s".', $wishList->getName(), $item->getProduct()->getName())
        );
        $this->assertSessionMessages($this->equalTo([(string)__($message)]), MessageInterface::TYPE_ERROR);
    }

    /**
     * @magentoConfigFixture current_store wishlist/general/multiple_enabled 1
     * @magentoDataFixture Magento/Wishlist/_files/two_wishlists_for_two_diff_customers.php
     *
     * @return void
     */
    public function testMoveItemsWhichNotRelevantToCustomer(): void
    {
        $this->customerSession->loginById(1);
        $wishList = $this->getWishlistByCustomerId->execute(1);
        $item = $this->getWishlistByCustomerId->getItemBySku(2, 'simple');
        $this->assertNotNull($item);
        $selected = [$item->getId() => 'on'];
        $params = ['wishlist_id' => $wishList->getWishlistId(), 'selected' => $selected];
        $this->performMoveItemsRequest($params);
        $message = $this->escaper->escapeHtml(
            sprintf('1 items cannot be moved: "%s".', $item->getProduct()->getName())
        );
        $this->assertSessionMessages($this->equalTo([(string)__($message)]), MessageInterface::TYPE_ERROR);
    }

    /**
     * Perform move wish list items request.
     *
     * @param array $params
     * @return void
     */
    private function performMoveItemsRequest(array $params): void
    {
        $this->getRequest()->setParams($params);
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->dispatch('wishlist/index/moveitems');
    }

    /**
     * Prepare wish list items to request.
     *
     * @param Collection $itemCollection
     * @return array
     */
    private function prepareItemsToRequest(Collection $itemCollection): array
    {
        $selected = [];
        $productNames = [];
        foreach ($itemCollection as $item) {
            $productNames[] = '"' . $item->getProduct()->getName() . '"';
            $selected[$item->getId()] = 'on';
        }

        return ['selected' => $selected, 'product_names' => $productNames];
    }
}
