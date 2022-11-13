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
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\Wishlist\Model\ResourceModel\Wishlist as WishlistResourceModel;
use Magento\Wishlist\Model\WishlistFactory;

/**
 * Test coverage for creating a wishlist
 */
class CreateWishlistTest extends GraphQlAbstract
{
    /**
     * @var CustomerTokenServiceInterface
     */
    private $customerTokenService;

    /**
     * @var WishlistFactory
     */
    private $wishlistFactory;

    /**
     * @var WishlistResourceModel
     */
    private $wishlistResource;

    /**
     * Set Up
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->customerTokenService = $objectManager->get(CustomerTokenServiceInterface::class);
        $this->wishlistFactory = $objectManager->get(WishlistFactory::class);
        $this->wishlistResource = $objectManager->get(WishlistResourceModel::class);
    }

    /**
     * Test creating the wishlist
     *
     * @magentoConfigFixture default_store wishlist/general/active 1
     * @magentoConfigFixture default_store wishlist/general/multiple_enabled 1
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     */
    public function testCreateWishlistSuccessfully(): void
    {
        $wishlistName = 'New Wishlist';
        $visibility = 'PUBLIC';
        $query = $this->getQuery($wishlistName, $visibility);
        $response = $this->graphQlMutation($query, [], '', $this->getHeaderMap());
        $this->assertArrayHasKey('createWishlist', $response);
        $wishlistResponse = $response['createWishlist']['wishlist'];
        $this->assertNotEmpty($wishlistResponse['id']);
        $this->assertNotNull($wishlistResponse['id']);
        $this->assertEquals(0, $wishlistResponse['items_count']);
        $this->assertEquals($wishlistName, $wishlistResponse['name']);
        $this->assertEquals($visibility, $wishlistResponse['visibility']);
        $this->assertCount(2, $wishlistResponse['items_v2']);
        $wishlistItemResponse = $wishlistResponse['items_v2']['items'];
        $this->assertEmpty($wishlistItemResponse, 'Wishlist items is not empty');
    }

    /**
     * Test creating a wishlist with disabled multiple wishlist feature
     *
     * @magentoConfigFixture default_store wishlist/general/active 1
     * @magentoConfigFixture default_store wishlist/general/multiple_enabled 0
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     */
    public function testCreatingWishlistWithDisabledMultipleWishlist(): void
    {
        $wishlistName = 'New Wishlist';
        $query = $this->getQuery($wishlistName);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The multiple wishlist configuration is currently disabled.');
        $this->graphQlMutation($query, [], '', $this->getHeaderMap());
    }

    /**
     * Test creating more wish lists than is allowed by configuration
     *
     * @magentoConfigFixture default_store wishlist/general/active 1
     * @magentoConfigFixture default_store wishlist/general/multiple_enabled 1
     * @magentoConfigFixture default_store wishlist/general/multiple_wishlist_number 1
     * @magentoApiDataFixture Magento/MultipleWishlist/_files/wishlists.php
     */
    public function testCreatingMoreThanIsAllowedWishLists(): void
    {
        $wishlistName = 'New Wishlist';
        $query = $this->getQuery($wishlistName);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Only 1 wish list(s) can be created.');
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
     * @param string $name
     * @param string $visibility
     *
     * @return string
     */
    private function getQuery(
        string $name,
        string $visibility = 'PUBLIC'
    ): string {
        return <<<MUTATION
mutation{
  createWishlist(input:
    {
    name:"{$name}"
      visibility:{$visibility}
  })
  {
    wishlist{
      id
      items_count
      items_v2{
        items{
          quantity
          id
          description
          product{sku name}
        }
        page_info{current_page page_size total_pages}}
      name
      visibility
      sharing_code
      updated_at
    }
  }
}
MUTATION;
    }
}
