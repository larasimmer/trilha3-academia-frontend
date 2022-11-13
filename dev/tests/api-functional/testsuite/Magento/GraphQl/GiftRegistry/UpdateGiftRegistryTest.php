<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\GiftRegistry;

use Magento\Customer\Api\Data\AddressInterface;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test for updating the gift registry
 */
class UpdateGiftRegistryTest extends GraphQlAbstract
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
     * Testing the customer gift registry removal
     *
     * @magentoApiDataFixture Magento/GiftRegistry/_files/gift_registry_entity_birthday_type.php
     *
     * @dataProvider giftRegistryInputDataProvider
     *
     * @param array $data
     * @throws AuthenticationException
     */
    public function testUpdateCustomerGiftRegistry(array $data): void
    {
        $authHeaders = $this->getCustomerAuthHeaders('customer@example.com', 'password');
        $giftRegistries = $this->graphQlQuery($this->getQuery(), [], '', $authHeaders);
        $this->assertNotEmpty($giftRegistries['customer']['gift_registries']);
        $giftRegistry = $giftRegistries['customer']['gift_registries'][0];

        $response = $this->graphQlMutation(
            $this->getUpdateMutation($giftRegistry['uid'], $data),
            [],
            '',
            $authHeaders
        );
        $this->assertArrayHasKey('updateGiftRegistry', $response);
        $updatedGiftRegistry = $response['updateGiftRegistry']['gift_registry'];
        $this->assertTrue($data['event_name'] === $updatedGiftRegistry['event_name']);
        $this->assertTrue($data['privacy_settings'] === $updatedGiftRegistry['privacy_settings']);
        $this->assertTrue($data['status'] === $updatedGiftRegistry['status']);

        foreach ($updatedGiftRegistry['dynamic_attributes'] as $dynamicAttribute) {
            $this->assertTrue(
                $dynamicAttribute['value'] === $data['dynamic_attributes'][$dynamicAttribute['code']]['value']
            );
        }

        $this->assertEquals(
            $data['shipping_address'][AddressInterface::POSTCODE],
            $updatedGiftRegistry['shipping_address'][AddressInterface::POSTCODE]
        );
        $this->assertEquals(
            $data['shipping_address'][AddressInterface::TELEPHONE],
            $updatedGiftRegistry['shipping_address'][AddressInterface::TELEPHONE]
        );
    }

    /**
     * Providing test gift registry data
     *
     * @return array
     */
    public function giftRegistryInputDataProvider(): array
    {
        return [
            [
                [
                    'event_name' => 'My New Event',
                    'privacy_settings' => 'PRIVATE',
                    'status' => 'INACTIVE',
                    'dynamic_attributes' => [
                        'event_country' => [
                            'value' => 'US'
                        ],
                        'event_date' => [
                            'value' => '2012-12-12'
                        ],
                    ],
                    'shipping_address' => [
                        AddressInterface::POSTCODE => '56789',
                        AddressInterface::TELEPHONE => '+14654568999',
                    ],
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
    private function getCustomerAuthHeaders(string $email, string $password): array
    {
        $customerToken = $this->customerTokenService->createCustomerAccessToken($email, $password);
        return ['Authorization' => 'Bearer ' . $customerToken];
    }

    /**
     * Get mutation
     *
     * @param string $uid
     * @param array $data
     *
     * @return string
     */
    private function getUpdateMutation(string $uid, array $data): string
    {
        return <<<MUTATION
mutation {
  updateGiftRegistry(
    giftRegistryUid: "{$uid}",
    giftRegistry: {
      event_name: "{$data['event_name']}",
      privacy_settings: {$data['privacy_settings']}
      status: {$data['status']}
      shipping_address: {
        address_data: {
          postcode: "{$data['shipping_address'][AddressInterface::POSTCODE]}"
          telephone: "{$data['shipping_address'][AddressInterface::TELEPHONE]}"
        }
      }
      dynamic_attributes: [{
        code: "event_date"
        value: "{$data['dynamic_attributes']['event_date']['value']}"
      }, {
        code: "event_country"
        value: "{$data['dynamic_attributes']['event_country']['value']}"
      }]
    }
  )
{
  gift_registry {
    event_name
    privacy_settings
    status
    dynamic_attributes {
      code
      value
    }
    shipping_address {
      telephone
      postcode
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
    }
  }
}
QUERY;
    }
}
