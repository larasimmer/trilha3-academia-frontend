<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MultipleWishlist\Model;

use Magento\AdvancedCheckout\Model\Cart;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Framework\Message\Manager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\MultipleWishlist\Helper\Data;
use Magento\Quote\Api\Data\CartInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Quote\Model\GetQuoteByReservedOrderId;
use Magento\Wishlist\Model\Wishlist;
use PHPUnit\Framework\TestCase;

/**
 * Class checks moving products from cart to wishlist with multiple wishlist enabled
 *
 * @magentoDbIsolation disabled
 */
class AdminMoveToWishlistFromCartTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var Cart */
    private $cart;

    /** @var GetQuoteByReservedOrderId */
    private $getQuoteByReservedOrderId;

    /** @var Data */
    private $wishListHelper;

    /** @var CustomerRegistry */
    private $customerRegistry;

    /** @var Registry */
    private $registry;

    /** @var Manager */
    private $messageManager;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->cart = $this->objectManager->get(Cart::class);
        $this->getQuoteByReservedOrderId = $this->objectManager->get(GetQuoteByReservedOrderId::class);
        $this->wishListHelper = $this->objectManager->get(Data::class);
        $this->customerRegistry = $this->objectManager->get(CustomerRegistry::class);
        $this->registry = $this->objectManager->get(Registry::class);
        $this->messageManager = $this->objectManager->get(Manager::class);
    }

    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_customer_without_address.php
     * @magentoDataFixture Magento/MultipleWishlist/_files/wishlists.php
     *
     * @return void
     */
    public function testMoveQuoteItemMultipleWishList(): void
    {
        $secondWishList = $this->getCustomerWishlistByCode(1, 'wishlist_fixture_2');
        $quote = $this->getQuoteByReservedOrderId->execute('test_order_with_customer_without_address');
        $item = $quote->getItemsCollection()->getFirstItem();
        $this->prepareCart($quote, 1);
        $this->cart->moveQuoteItem((int)$item->getId(), "wishlist_{$secondWishList->getId()}");
        $secondUpdatedWishList = $this->getCustomerWishlistByCode(1, 'wishlist_fixture_2');
        $wishListItem = $secondUpdatedWishList->getItemCollection()->getFirstItem();
        $this->assertNotNull($wishListItem->getId());
        $this->assertEquals((int)$item->getProductId(), (int)$wishListItem->getProductId());
        $this->assertEquals(2, (int)$wishListItem->getQty());
    }

    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_customer_without_address.php
     *
     * @return void
     */
    public function testMoveQuoteItemToUnExistingWishlist(): void
    {
        $quote = $this->getQuoteByReservedOrderId->execute('test_order_with_customer_without_address');
        $this->prepareCart($quote, 1);
        $itemId = $quote->getItemsCollection()->getFirstItem()->getId();
        $this->cart->moveQuoteItem((int)$itemId, 'wishlist_725');
        $message = $this->messageManager->getMessages()->getLastAddedMessage();
        $this->assertNotNull($message);
        $this->assertEquals((string)__('We can\'t find this wish list.'), $message->getText());
    }

    /**
     * Get customer wishlist by sharing code
     *
     * @param int $customerId
     * @param string $sharingCode
     * @return Wishlist
     */
    private function getCustomerWishlistByCode(int $customerId, string $sharingCode): Wishlist
    {
        $this->registry->unregister('wishlists_by_customer');
        $wishlists = $this->wishListHelper->getCustomerWishlists($customerId);

        return $wishlists->getItemByColumnValue('sharing_code', $sharingCode);
    }

    /**
     * Prepare cart object for test execution
     *
     * @param CartInterface $quote
     * @param int $customerId
     * @retrun void
     */
    private function prepareCart(CartInterface $quote, int $customerId): void
    {
        $this->cart->setCustomer($this->customerRegistry->retrieve($customerId));
        $this->cart->setQuote($quote);
    }
}
