<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\MultipleWishlist;

use Exception;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\MultipleWishlist\Helper\Data as MultipleWishlistHelper;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test coverage for deleting a wishlist
 */
class DeleteWishlistTest extends GraphQlAbstract
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
     * Testing successfully deleting a wishlist
     *
     * @magentoConfigFixture default_store wishlist/general/active 1
     * @magentoConfigFixture default_store wishlist/general/multiple_enabled 1
     * @magentoApiDataFixture Magento/MultipleWishlist/_files/wishlists_rollback.php
     * @magentoApiDataFixture Magento/MultipleWishlist/_files/wishlists.php
     */
    public function testDeletingTheWishlistSuccessfully(): void
    {
        $customerId = 1;
        $customerWishLists = $this->multipleWishlistHelper->getCustomerWishlists($customerId);
        $initialNumberOfWishLists = $customerWishLists->getSize();
        $firstWishlist = $customerWishLists->getFirstItem();
        $secondWishlist = $customerWishLists->getLastItem();

        $query = $this->deleteWishlistQuery($secondWishlist->getId());
        $response = $this->graphQlMutation($query, [], '', $this->getHeaderMap());

        $this->assertArrayHasKey('deleteWishlist', $response);
        $this->assertTrue($response['deleteWishlist']['status']);
        $this->assertArrayHasKey('wishlists', $response['deleteWishlist']);
        $this->assertNotEmpty($response['deleteWishlist']['wishlists']);
        $this->assertEquals($firstWishlist->getId(), $response['deleteWishlist']['wishlists'][0]['id']);
        $this->assertEquals($firstWishlist->getName(), $response['deleteWishlist']['wishlists'][0]['name']);
        $wishlistItemResponse = $response['deleteWishlist']['wishlists'][0]['items_v2']['items'];
        $this->assertNotEmpty($wishlistItemResponse);
        $this->assertEquals('simple1', $wishlistItemResponse[0]['product']['sku']);

        $actualWishListsQuery = $this->getCustomerWishListsQuery();
        $response = $this->graphQlQuery($actualWishListsQuery, [], '', $this->getHeaderMap());
        $this->assertTrue(($initialNumberOfWishLists - 1) === count($response['customer']['wishlists']));
    }

    /**
     * Testing deleting a wishlist by unauthorized customer
     *
     * @magentoConfigFixture default_store wishlist/general/active 1
     * @magentoConfigFixture default_store wishlist/general/multiple_enabled 1
     * @magentoApiDataFixture Magento/MultipleWishlist/_files/wishlists.php
     */
    public function testDeletingTheWishlistByUnauthorizedCustomer(): void
    {
        $customerId = 1;
        $customerWishLists = $this->multipleWishlistHelper->getCustomerWishlists($customerId);
        $wishlist = $customerWishLists->getLastItem();
        $query = $this->deleteWishlistQuery($wishlist->getId());
        $this->expectExceptionMessage('The wish list is not assigned to your account and can\'t be edited.');
        $this->graphQlMutation($query, [], '', $this->getHeaderMap('customer_two@example.com'));
    }

    /**
     * Testing the attempt to remove the default wishlist
     *
     * @magentoConfigFixture default_store wishlist/general/active 1
     * @magentoConfigFixture default_store wishlist/general/multiple_enabled 1
     * @magentoApiDataFixture Magento/MultipleWishlist/_files/wishlists.php
     */
    public function testDeletingTheDefaultWishlist(): void
    {
        $customerId = 1;
        $wishLists = $this->multipleWishlistHelper->getCustomerWishlists($customerId);
        $defaultWishlist = $wishLists->getFirstItem();

        $query = $this->deleteWishlistQuery($defaultWishlist->getId());
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The default wish list can\'t be deleted.');
        $this->graphQlMutation($query, [], '', $this->getHeaderMap());
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
     * @param string $wishlistId
     *
     * @return string
     */
    private function deleteWishlistQuery(
        string $wishlistId
    ): string {
        return <<<MUTATION
mutation {
  deleteWishlist(wishlistId: "$wishlistId")
    {
    status
    wishlists{
      id
      name
      visibility
      items_v2{page_info{current_page} items{product{sku}}}
    }
  }
}
MUTATION;
    }

    /**
     * Returns GraphQl customer wish lists query
     *
     * @return string
     */
    private function getCustomerWishListsQuery(): string
    {
        return <<<QUERY
query {
  customer {
    wishlists {
      id
    }
  }
}
QUERY;
    }
}
