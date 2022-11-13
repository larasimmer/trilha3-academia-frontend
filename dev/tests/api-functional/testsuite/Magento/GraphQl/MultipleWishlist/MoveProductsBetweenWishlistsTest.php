<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\MultipleWishlist;

use Magento\Framework\Exception\AuthenticationException;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\MultipleWishlist\Helper\Data as MultipleWishlistHelper;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\Wishlist\Model\Wishlist;

/**
 * Test coverage for moving a product to wishlist
 */
class MoveProductsBetweenWishlistsTest extends GraphQlAbstract
{
    /**
     * @var CustomerTokenServiceInterface
     */
    private $customerTokenService;

    /**
     * @var MultipleWishlistHelper
     */
    private $multipleWishlistHelper;

    /**
     * Set Up
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->customerTokenService = $objectManager->get(CustomerTokenServiceInterface::class);
        $this->multipleWishlistHelper = $objectManager->get(MultipleWishlistHelper::class);
    }

    /**
     * Testing moving products to another wishlist
     *
     * @magentoConfigFixture default_store wishlist/general/active 1
     * @magentoConfigFixture default_store wishlist/general/multiple_enabled 1
     * @magentoApiDataFixture Magento/MultipleWishlist/_files/wishlists_with_two_items.php
     */
    public function testMovingProductsToAnotherWishlist(): void
    {
        $quantity = 1;
        $customerId = 1;
        $customerWishLists = $this->multipleWishlistHelper->getCustomerWishlists($customerId);

        /** @var Wishlist $sourceWishlist */
        $sourceWishlist = $customerWishLists->getFirstItem();
        $sourceWishlistUid = $sourceWishlist->getId();
        /** @var Wishlist $destinationWishlist */
        $destinationWishlist = $customerWishLists->getLastItem();
        $destinationWishlistUid = $destinationWishlist->getId();
        $initialDestinationItemsCount = $destinationWishlist->getItemsCount();
        $itemMoved = $sourceWishlist->getItemCollection()->getFirstItem();
        $itemNotMoved = $sourceWishlist->getItemCollection()->getLastItem();
        $query = $this->getQuery($sourceWishlistUid, $destinationWishlistUid, $itemMoved->getId(), $quantity);
        $response = $this->graphQlMutation($query, [], '', $this->getHeaderMap());

        $this->assertArrayHasKey('moveProductsBetweenWishlists', $response);
        $this->assertArrayHasKey('source_wishlist', $response['moveProductsBetweenWishlists']);
        $this->assertArrayHasKey('destination_wishlist', $response['moveProductsBetweenWishlists']);

        $sourceWishlistResponse = $response['moveProductsBetweenWishlists']['source_wishlist'];
        $destinationWishlistResponse = $response['moveProductsBetweenWishlists']['destination_wishlist'];
        $destinationWishlistItemResponse = $destinationWishlistResponse['items_v2']['items'][0];

        $this->assertEquals($destinationWishlistResponse['items_count'], $sourceWishlistResponse['items_count']);
        $this->assertTrue($destinationWishlistResponse['items_count'] === $initialDestinationItemsCount + 1);
        $this->assertEquals('Second Wish List', $destinationWishlistResponse['name']);
        $this->assertEquals('First Wish List', $sourceWishlistResponse['name']);
        $this->assertEquals($itemMoved->getProduct()->getSku(), $destinationWishlistItemResponse['product']['sku']);

        // Get customer wishlist by passing source wishlist id
        $query = $this->getCustomerWishlistQuery((int) $sourceWishlist->getId());
        $response = $this->graphQlMutation($query, [], '', $this->getHeaderMap());
        $sourceWishlistItems = $response['customer']['wishlist_v2']['items_v2']['items'];
        //Customer's source wishlist only contains items that are not moved
        $this->assertEquals($itemNotMoved->getProduct()->getSku(), $sourceWishlistItems[0]['product']['sku']);
        $this->assertEquals($itemNotMoved->getProduct()->getSku(), $sourceWishlistResponse['items_v2']['items'][0]['product']['sku']);
    }

    /**
     * Testing moving products to another wishlist by unauthorized customers
     *
     * @magentoConfigFixture default_store wishlist/general/active 1
     * @magentoConfigFixture default_store wishlist/general/multiple_enabled 1
     * @magentoApiDataFixture Magento/MultipleWishlist/_files/wishlists_with_two_items.php
     */
    public function testMovingProductsFromWishlistByUnauthorizedCustomers(): void
    {
        $customerId = 1;
        $quantity = 1;
        $customerWishLists = $this->multipleWishlistHelper->getCustomerWishlists($customerId);

        /** @var Wishlist $sourceWishlist */
        $sourceWishlist = $customerWishLists->getFirstItem();
        $sourceWishlistUid = $sourceWishlist->getId();
        /** @var Wishlist $destinationWishlist */
        $destinationWishlist = $customerWishLists->getLastItem();
        $destinationWishlistUid = $destinationWishlist->getId();
        $itemMoved = $sourceWishlist->getItemCollection()->getFirstItem();
        $query = $this->getQuery($sourceWishlistUid, $destinationWishlistUid, $itemMoved->getId(), $quantity);
        $this->expectExceptionMessage('The wish list is not assigned to your account and can\'t be edited.');
        $this->graphQlMutation($query, [], '', $this->getHeaderMap('customer_two@example.com'));
    }

    /**
     * Authentication header map
     *
     * @param string $username
     * @param string $password
     *
     * @return array
     *
     * @throws AuthenticationException
     */
    private function getHeaderMap(string $username = 'customer@example.com', string $password = 'password'): array
    {
        $customerToken = $this->customerTokenService->createCustomerAccessToken($username, $password);

        return ['Authorization' => 'Bearer ' . $customerToken];
    }

    /**
     * Returns GraphQl mutation string
     *
     * @param int $wishlistId
     * @param int $wishlistItemId
     * @param int $quantity
     *
     * @return string
     */
    private function getQuery(
        string $sourceWishlistUid,
        string $destinationWishlistUid,
        string $wishlistItemUid,
        int $quantity
    ): string {
        return <<<MUTATION
mutation {
  moveProductsBetweenWishlists(
    sourceWishlistUid: "{$sourceWishlistUid}"
    destinationWishlistUid: "{$destinationWishlistUid}"
    wishlistItems: [
      {
        wishlist_item_id: "{$wishlistItemUid}"
        quantity: {$quantity}
      }
    ]
  ) {
    user_errors {
      message
    }
 source_wishlist {
      name
      items_count
      items_v2 {items {quantity id product{name sku}}}
    }
    destination_wishlist {
      name
      items_count
      items_v2 {items {quantity id product{name sku}}
      }
    }
  }
}
MUTATION;
    }

    /**
     * Get customer wishlist query
     *
     * @param int $wishlistId
     *
     * @return string
     */
    private function getCustomerWishlistQuery(int $wishlistId): string
    {
        return <<<QUERY
query {
  customer {
    wishlist_v2(id: $wishlistId) {
      items_count
      items_v2 {
        items{quantity id product {name sku}}
      }
    }
  }
}
QUERY;
    }
}
