<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\GiftRegistry;

use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test for creating a new gift registry
 */
class CreateGiftRegistryTest extends GraphQlAbstract
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
     * Testing the customer gift registry creation
     *
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     *
     * @dataProvider giftRegistryInputDataProvider
     *
     * @param array $data
     *
     * @throws AuthenticationException
     */
    public function testCreateCustomerGiftRegistry(array $data): void
    {
        $authHeaders = $this->getCustomerAuthHeaders('customer@example.com', 'password');
        $response = $this->graphQlMutation($this->getQuery($data), [], '', $authHeaders);
        $this->assertArrayHasKey('createGiftRegistry', $response);
        $this->assertArrayHasKey('gift_registry', $response['createGiftRegistry']);
        $this->assertNotEmpty($response['createGiftRegistry']['gift_registry']);
        $giftRegistryResult = $response['createGiftRegistry']['gift_registry'];
        $giftRegistry = $this->graphQlQuery(
            $this->getGiftRegistryQuery($giftRegistryResult['uid']),
            [],
            '',
            $authHeaders
        );
        $giftRegistry = $giftRegistry['customer']['gift_registry'];
        $this->assertEquals($giftRegistry['uid'], $giftRegistryResult['uid']);
        $this->assertEquals('PRIVATE', $giftRegistryResult['privacy_settings']);
        $this->assertEquals('ACTIVE', $giftRegistryResult['status']);
        $this->assertNotEmpty($giftRegistryResult['created_at']);
        $this->assertNotEmpty($giftRegistryResult['dynamic_attributes']);
        $this->assertNotEmpty($giftRegistryResult['registrants']);
        $this->assertNotEmpty($giftRegistryResult['shipping_address']);
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
                    'event_name' => 'My Event',
                    'message' => 'My Event Message',
                    'privacy_settings' => 'PRIVATE',
                    'status' => 'ACTIVE',
                    'typeUid' => 'MQ==',
                    'address_data' => [
                        AddressInterface::FIRSTNAME => 'First',
                        AddressInterface::LASTNAME => 'Last',
                        AddressInterface::CITY => 'City',
                        AddressInterface::STREET => 'My street',
                        AddressInterface::TELEPHONE => '123123123',
                        AddressInterface::POSTCODE => '3322',
                        AddressInterface::COUNTRY_ID => 'MD'
                    ],
                    'registrants' => [
                        [
                            CustomerInterface::FIRSTNAME => 'Reg First',
                            CustomerInterface::LASTNAME => 'Reg last',
                            CustomerInterface::EMAIL => 'reg-1@example.com',
                        ], [
                            CustomerInterface::FIRSTNAME => 'Reg Second',
                            CustomerInterface::LASTNAME => 'Reg last',
                            CustomerInterface::EMAIL => 'reg-2@example.com',
                        ],
                    ],
                    'dynamic_attributes' => [
                        'event_country' => [
                            'value' => 'MD'
                        ],
                        'event_date' => [
                            'value' => '2021-01-01'
                        ],
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
     * @param array $data
     *
     * @return string
     */
    private function getQuery(array $data): string
    {
        return <<<QUERY
mutation {
  createGiftRegistry(
	giftRegistry: {
      event_name: "{$data['event_name']}",
      message: "{$data['message']}"
      privacy_settings: {$data['privacy_settings']}
      status: {$data['status']}
      gift_registry_type_uid: "{$data['typeUid']}"
      shipping_address: {
        address_data: {
          firstname: "{$data['address_data'][AddressInterface::FIRSTNAME]}"
          lastname: "{$data['address_data'][AddressInterface::LASTNAME]}"
          city: "{$data['address_data'][AddressInterface::CITY]}"
          street: ["{$data['address_data'][AddressInterface::STREET]}"]
          telephone: "{$data['address_data'][AddressInterface::TELEPHONE]}"
          postcode: "{$data['address_data'][AddressInterface::POSTCODE]}"
          country_id: {$data['address_data'][AddressInterface::COUNTRY_ID]}
        }
      }
      registrants: [
      {
        firstname: "{$data['registrants'][0][CustomerInterface::FIRSTNAME]}"
        lastname: "{$data['registrants'][0][CustomerInterface::LASTNAME]}"
        email: "{$data['registrants'][0][CustomerInterface::EMAIL]}"
      }, {
        firstname: "{$data['registrants'][1][CustomerInterface::FIRSTNAME]}"
        lastname: "{$data['registrants'][1][CustomerInterface::LASTNAME]}"
        email: "{$data['registrants'][1][CustomerInterface::EMAIL]}"
      }
    ]
    dynamic_attributes: [{
      code: "event_country"
      value: "{$data['dynamic_attributes']['event_country']['value']}"
    }, {
      code: "event_date"
      value: "{$data['dynamic_attributes']['event_date']['value']}"
    }]
  }
) {
  gift_registry {
    uid
    event_name
    privacy_settings
    status
    created_at
    dynamic_attributes {
      code
      value
    }
    registrants {
      uid
      firstname
      lastname
    }
    shipping_address {
      city
      country_id
      postcode
    }
  }
 }
}
QUERY;
    }

    /**
     * Get query
     *
     * @param string $uid
     *
     * @return string
     */
    private function getGiftRegistryQuery(string $uid): string
    {
        return <<<QUERY
query {
  customer {
    gift_registry (giftRegistryUid: "{$uid}") {
      uid
      type {
        label
      }
      event_name
      message
      owner_name
      registrants {
        firstname
        lastname
      }
      shipping_address {
        city
        country_code
        region_id
      }
    }
  }
}
QUERY;
    }
}
