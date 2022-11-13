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
 * Test coverage for adding a product to specified wishlist
 */
class AddProductToSpecificWishlistTest extends GraphQlAbstract
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
     * @magentoConfigFixture default_store wishlist/general/active 1
     * @magentoConfigFixture default_store wishlist/general/multiple_enabled 1
     * @magentoApiDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoApiDataFixture Magento/MultipleWishlist/_files/wishlists_with_two_items.php
     */
    public function testAddSimpleProductToSpecificWishlist(): void
    {
        $customerId = 1;
        $quantity = 5;
        $customerWishLists = $this->multipleWishlistHelper->getCustomerWishlists($customerId);
        $sku = 'simple';
        /** @var Wishlist $wishlist */
        $wishlist = $customerWishLists->getLastItem();
        $initialNumberOfItems = $wishlist->getItemCollection()->getSize();
        $query = $this->getQuery($wishlist->getId(), $sku, $quantity);
        $response = $this->graphQlMutation($query, [], '', $this->getHeaderMap());

        $this->assertArrayHasKey('addProductsToWishlist', $response);
        $this->assertArrayHasKey('wishlist', $response['addProductsToWishlist']);
        $wishlistResponse = $response['addProductsToWishlist']['wishlist'];
        // one item was added to the wishlist
        $this->assertTrue($wishlistResponse['items_count'] === $initialNumberOfItems + 1);
        $wishlistItemResponse = $wishlistResponse['items_v2']['items'];
        $this->assertCount(1, $wishlistItemResponse);
        $this->assertEquals($quantity, $wishlistItemResponse[0]['quantity']);
        $pageInfo =
            [
            'current_page' => 1,
            'page_size' => 20,
            'total_pages' => 1
        ];
        $this->assertResponseFields($wishlistResponse['items_v2']['page_info'], $pageInfo);
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
     * @param string $sku
     * @param int $quantity
     *
     * @return string
     */
    private function getQuery(
        string $wishlistId,
        string $sku,
        int $quantity
    ): string {
        return <<<MUTATION
mutation {
  addProductsToWishlist(
    wishlistId: "{$wishlistId}",
    wishlistItems: [
    {
      sku: "{$sku}"
      quantity: {$quantity}
    }
    ]
) {
    user_errors {
      code
      message
    }
    wishlist {
      id
      items_count
        items_v2 {
          items {
           quantity
            id
            product {sku name}
         }
        page_info {current_page page_size total_pages}
      }
    }
  }
}
MUTATION;
    }
}
