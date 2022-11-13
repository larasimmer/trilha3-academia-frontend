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
 * Test coverage for copying a product to wishlist
 */
class CopyProductsBetweenWishlistsTest extends GraphQlAbstract
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
     * Testing copying products to another wishlist
     *
     * @magentoConfigFixture default_store wishlist/general/active 1
     * @magentoConfigFixture default_store wishlist/general/multiple_enabled 1
     * @magentoApiDataFixture Magento/MultipleWishlist/_files/wishlists_with_two_items.php
     */
    public function testCopyingProductsToWishlist(): void
    {
        $customerId = 1;
        $quantity = 1;
        $customerWishLists = $this->multipleWishlistHelper->getCustomerWishlists($customerId);

        /** @var Wishlist $firstWishlist */
        $firstWishlist = $customerWishLists->getFirstItem();
        $firstWishlistUid = $firstWishlist->getId();
        /** @var Wishlist $secondWishlist */
        $secondWishlist = $customerWishLists->getLastItem();
        $secondWishlistUid = $secondWishlist->getId();
        $sourceWishlistItemsCount = $firstWishlist->getItemsCount();
        $initialNumberOfItems = $secondWishlist->getItemsCount();
        $itemCopied = $firstWishlist->getItemCollection()->getFirstItem();

        $query = $this->getQuery($firstWishlistUid, $secondWishlistUid, $itemCopied->getId(), $quantity);
        $response = $this->graphQlMutation($query, [], '', $this->getHeaderMap());

        $this->assertArrayHasKey('copyProductsBetweenWishlists', $response);
        $this->assertArrayHasKey('source_wishlist', $response['copyProductsBetweenWishlists']);
        $this->assertArrayHasKey('destination_wishlist', $response['copyProductsBetweenWishlists']);
        $destinationWishlistResponse = $response['copyProductsBetweenWishlists']['destination_wishlist'];
        $sourceWishlistResponse = $response['copyProductsBetweenWishlists']['source_wishlist'];
        $this->assertEquals('Second Wish List', $destinationWishlistResponse['name']);
        $this->assertEquals('First Wish List', $sourceWishlistResponse['name']);
        $this->assertEquals($sourceWishlistItemsCount, $sourceWishlistResponse['items_count']);

        //destination wishlist has now 1 item that is copied from source wishlist
        $this->assertTrue($destinationWishlistResponse['items_count'] === $initialNumberOfItems + 1);
        $destinationWishlistItemResponse = $response['copyProductsBetweenWishlists']['destination_wishlist']['items_v2']['items'][0];
        $this->assertEquals($quantity, $destinationWishlistItemResponse['quantity']);
        //Verify that only 1 item is copied to destination wishlist
        $this->assertCount(1, $response['copyProductsBetweenWishlists']['destination_wishlist']['items_v2']['items']);
        //Verify that the correct product is copied to the destination wishlist
        $this->assertEquals($itemCopied->getData()['name'], $destinationWishlistItemResponse['product']['name']);
    }

    /**
     * Testing copying products to another wishlist by unauthorized customers.
     *
     * @magentoConfigFixture default_store wishlist/general/active 1
     * @magentoConfigFixture default_store wishlist/general/multiple_enabled 1
     * @magentoApiDataFixture Magento/MultipleWishlist/_files/wishlists_with_two_items.php
     */
    public function testCopyingProductsFromWishlistByUnauthorizedCustomers(): void
    {
        $customerId = 1;
        $quantity = 1;
        $customerWishLists = $this->multipleWishlistHelper->getCustomerWishlists($customerId);

        /** @var Wishlist $firstWishlist */
        $firstWishlist = $customerWishLists->getFirstItem();
        $firstWishlistUid = $firstWishlist->getId();
        $secondWishlist = $customerWishLists->getLastItem();
        $secondWishlistUid = $secondWishlist->getId();
        $itemCopied = $firstWishlist->getItemCollection()->getFirstItem();
        //$query = $this->getQuery((int) $secondWishlist->getId(), (int) $item->getId(), $quantity);
        $query = $this->getQuery($firstWishlistUid, $secondWishlistUid, $itemCopied->getId(), $quantity);
        $this->expectExceptionMessage('The wish list is not assigned to your account and can\'t be edited.');
        $this->graphQlMutation($query, [], '', $this->getHeaderMap('customer_two@example.com'));
    }

    /**
     * Verify that user error is returned when trying to copy more items than source wishlist has
     *
     * @magentoConfigFixture default_store wishlist/general/active 1
     * @magentoConfigFixture default_store wishlist/general/multiple_enabled 1
     * @magentoApiDataFixture Magento/MultipleWishlist/_files/wishlists_with_two_items.php
     */
    public function testCopyingMoreProducts(): void
    {
        $customerId = 1;
        $quantity = 3;
        $customerWishLists = $this->multipleWishlistHelper->getCustomerWishlists($customerId);

        /** @var Wishlist $firstWishlist */
        $firstWishlist = $customerWishLists->getFirstItem();
        $firstWishlistUid = $firstWishlist->getId();
        /** @var Wishlist $secondWishlist */
        $secondWishlist = $customerWishLists->getLastItem();
        $secondWishlistUid = $secondWishlist->getId();
        $itemCopied = $firstWishlist->getItemCollection()->getFirstItem();
        $qtyThatCanBeCopied = $itemCopied->getQty();
        $query = $this->getQuery($firstWishlistUid, $secondWishlistUid, $itemCopied->getId(), $quantity);
        $response = $this->graphQlMutation($query, [], '', $this->getHeaderMap());

        $this->assertArrayHasKey('copyProductsBetweenWishlists', $response);
        $this->assertArrayHasKey('source_wishlist', $response['copyProductsBetweenWishlists']);
        $this->assertArrayHasKey('destination_wishlist', $response['copyProductsBetweenWishlists']);
        $errors = $response['copyProductsBetweenWishlists']['user_errors'];
        $this->assertNotEmpty($errors);
        $expectedMessage = 'The maximum quantity that can be copied for "' . $itemCopied->getProduct()->getSku() . '" is ' . $qtyThatCanBeCopied . '.';
        $this->assertEquals($expectedMessage, $errors[0]['message']);
        //Hence no items are copied to destination wishlist
        $this->assertEmpty($response['copyProductsBetweenWishlists']['destination_wishlist']['items_v2']['items']);
    }

    /**
     * Testing copying existing products to wishlist that should return a error message
     *
     * @magentoConfigFixture default_store wishlist/general/active 1
     * @magentoConfigFixture default_store wishlist/general/multiple_enabled 1
     * @magentoApiDataFixture Magento/MultipleWishlist/_files/wishlists_with_two_items.php
     */
    public function testCopyingExistingProductsToWishlist(): void
    {
        $customerId = 1;
        $quantity = 1;
        $customerWishLists = $this->multipleWishlistHelper->getCustomerWishlists($customerId);

        /** @var Wishlist $wishlist */
        $firstWishlist = $customerWishLists->getFirstItem();
        $firstWishlistUid = $firstWishlist->getId();
        $itemCopied = $firstWishlist->getItemCollection()->getFirstItem();
        $query = $this->getQuery($firstWishlistUid, $firstWishlistUid, $itemCopied->getId(), $quantity);
        $response = $this->graphQlMutation($query, [], '', $this->getHeaderMap());

        $expectedErrorMessage = sprintf(
            'The "%s" is already present in "%s"',
            $itemCopied->getProductName(),
            $firstWishlist->getName()
        );

        $this->assertArrayHasKey('source_wishlist', $response['copyProductsBetweenWishlists']);
        $this->assertArrayHasKey('destination_wishlist', $response['copyProductsBetweenWishlists']);
        $wishlistResponse = $response['copyProductsBetweenWishlists']['destination_wishlist'];
        $inputErrors = $response['copyProductsBetweenWishlists']['user_errors'];
        $this->assertTrue($wishlistResponse['items_count'] === $firstWishlist->getItemsCount());
        $this->assertEquals($expectedErrorMessage, $inputErrors[0]['message']);
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
  copyProductsBetweenWishlists(
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
      items_v2 {items {quantity id}}
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
}
