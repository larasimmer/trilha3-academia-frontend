<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\GiftRegistry;

use Magento\Framework\Exception\AuthenticationException;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test for updating the gift registry items
 */
class UpdateGiftRegistryItemsTest extends GraphQlAbstract
{
    /**
     * @var CustomerTokenServiceInterface
     */
    private $customerTokenService;

    /**
     * Set Up
     */
    protected function setUp(): void
    {
        $this->customerTokenService = Bootstrap::getObjectManager()->get(CustomerTokenServiceInterface::class);
    }

    /**
     * Test for updating a gift registry item
     *
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/Catalog/_files/products.php
     * @magentoApiDataFixture Magento/GiftRegistry/_files/resource_item_collection.php
     *
     * @dataProvider giftRegistryItemInputDataProvider
     *
     * @param array $itemData
     *
     * @throws AuthenticationException
     */
    public function testUpdateCustomerGiftRegistryItems(array $itemData): void
    {
        $headers = $this->getAuthHeaders('customer@example.com', 'password');
        $giftRegistries = $this->graphQlQuery($this->getQuery(), [], '', $headers);
        $this->assertNotEmpty($giftRegistries['customer']['gift_registries']);
        $giftRegistry = $giftRegistries['customer']['gift_registries'][0];
        $item = $giftRegistry['items'][0];
        $itemData['uid'] = $item['uid'];
        $response = $this->graphQlMutation(
            $this->getUpdateMutation($giftRegistry['uid'], $itemData),
            [],
            '',
            $headers
        );
        $this->assertArrayHasKey('updateGiftRegistryItems', $response);
        $updatedGiftRegistry = $response['updateGiftRegistryItems']['gift_registry'];
        $items = $updatedGiftRegistry['items'];
        $this->assertEquals($itemData['uid'], $item['uid']);
        $this->assertEquals($itemData['quantity'], $items[0]['quantity']);
        $this->assertEquals($itemData['note'], $items[0]['note']);
    }

    /**
     * Test for updating a wrong gift registry item
     *
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/Catalog/_files/products.php
     * @magentoApiDataFixture Magento/GiftRegistry/_files/resource_item_collection.php
     *
     * @throws AuthenticationException
     */
    public function testUpdateWrongGiftRegistryItem(): void
    {
        $customerHeaders = $this->getAuthHeaders('customer@example.com', 'password');
        $giftRegistries = $this->graphQlQuery($this->getQuery(), [], '', $customerHeaders);
        $this->assertNotEmpty($giftRegistries['customer']['gift_registries']);
        $giftRegistry = $giftRegistries['customer']['gift_registries'][0];
        $itemData['uid'] = 'test';
        $itemData['quantity'] = 5;
        $itemData['note'] = 'Test';
        $this->expectExceptionMessage(
            sprintf('The item(s) "%s" does not exist', $itemData['uid'])
        );
        $this->graphQlMutation(
            $this->getUpdateMutation($giftRegistry['uid'], $itemData),
            [],
            '',
            $customerHeaders
        );
    }

    /**
     * Providing test gift registry item data
     *
     * @return array
     */
    public function giftRegistryItemInputDataProvider(): array
    {
        return [
            [
                [
                    'quantity' => 4,
                    'note' => 'New note'
                ], [
                    'quantity' => 5,
                ]
            ]
        ];
    }

    /**
     * Get customer auth headers
     *
     * @param string $email
     * @param string $password
     *
     * @return array
     *
     * @throws AuthenticationException
     */
    private function getAuthHeaders(string $email, string $password): array
    {
        $customerToken = $this->customerTokenService->createCustomerAccessToken($email, $password);
        return ['Authorization' => 'Bearer ' . $customerToken];
    }

    /**
     * Get mutation
     *
     * @param string $uid
     * @param array $itemData
     *
     * @return string
     */
    private function getUpdateMutation(string $uid, array $itemData): string
    {
        return <<<MUTATION
mutation {
  updateGiftRegistryItems(
    giftRegistryUid: "{$uid}"
    items: {
      gift_registry_item_uid: "{$itemData['uid']}"
      quantity: {$itemData['quantity']}
      note: "{$itemData['note']}"
    }
  ) {
    gift_registry {
      items {
        uid
        quantity
        note
        product {
          sku
          name
        }
      }
    }
  }
}
MUTATION;
    }

    /**
     * Get query
     *
     * @return string
     */
    private function getQuery(): string
    {
        return <<<QUERY
query {
  customer {
    gift_registries {
      uid
      items {
        uid
        quantity
        note
      }
    }
  }
}
QUERY;
    }
}
