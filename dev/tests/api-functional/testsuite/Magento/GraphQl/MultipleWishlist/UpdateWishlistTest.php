<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\MultipleWishlist;

use Magento\Framework\Exception\AuthenticationException;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\Wishlist\Model\ResourceModel\Wishlist as WishlistResourceModel;
use Magento\Wishlist\Model\WishlistFactory;

/**
 * Test coverage for updating the wishlist
 */
class UpdateWishlistTest extends GraphQlAbstract
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
     *
     * @throws AuthenticationException
     */
    public function testUpdateWishlistSuccessfully(): void
    {
        $wishlistName = 'My Updated Wishlist';
        $privateVisibility = 'PRIVATE';
        $createWishlistId = $this->createWishlist('My Wish List', 'PUBLIC');
        $query = $this->getQuery($createWishlistId, $wishlistName, 'PRIVATE');
        $response = $this->graphQlMutation($query, [], '', $this->getHeaderMap());
        $this->assertArrayHasKey('updateWishlist', $response);
        $updateWishlistResponse = $response['updateWishlist'];
        $this->assertEquals($wishlistName, $updateWishlistResponse['name']);
        $this->assertEquals($privateVisibility, $updateWishlistResponse['visibility']);
        $this->assertEquals($createWishlistId, $updateWishlistResponse['uid']);
    }

    /**
     * Create a new customer wishlist
     *
     * @param string $name
     * @param string $visibility
     *
     * @return string
     *
     * @throws AuthenticationException
     */
    private function createWishlist(string $name, string $visibility = 'PUBLIC'): string
    {
        $query = $this->getCreateWishlistQuery($name, $visibility);
        $response = $this->graphQlMutation($query, [], '', $this->getHeaderMap());

        return $response['createWishlist']['wishlist']['id'];
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
     * Returns GraphQl create wishlist mutation query
     *
     * @param string $name
     * @param string $visibility
     *
     * @return string
     */
    private function getCreateWishlistQuery(
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

    /**
     * Returns GraphQl mutation string
     *
     * @param int $id
     * @param string $name
     * @param string $visibility
     *
     * @return string
     */
    private function getQuery(
        string $id,
        string $name,
        string $visibility
    ): string {
        return <<<MUTATION
mutation {
  updateWishlist(wishlistId: "{$id}", name: "{$name}", visibility: {$visibility}) {
    uid
    name
    visibility
  }
}
MUTATION;
    }
}
