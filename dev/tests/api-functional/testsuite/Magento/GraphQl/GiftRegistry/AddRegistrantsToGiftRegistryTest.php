<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\GiftRegistry;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test for adding registrants to gift registry
 */
class AddRegistrantsToGiftRegistryTest extends GraphQlAbstract
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
     * Testing adding registrants to gift registry
     *
     * @magentoApiDataFixture Magento/GiftRegistry/_files/gift_registry_entity_birthday_type.php
     *
     * @dataProvider registrantDataProvider
     *
     * @param array $registrantData
     *
     * @throws AuthenticationException
     */
    public function testAddingRegistrantsToGiftRegistry(array $registrantData): void
    {
        $authHeaders = $this->getCustomerAuthHeaders('customer@example.com', 'password');
        $giftRegistry = $this->getGiftRegistry();
        $response = $this->graphQlMutation(
            $this->getMutation($giftRegistry['uid'], $registrantData),
            [],
            '',
            $authHeaders
        );
        $this->assertNotEmpty($response['addGiftRegistryRegistrants']['gift_registry']);
        $giftRegistryResult = $response['addGiftRegistryRegistrants']['gift_registry'];
        $this->assertTrue(
            count($giftRegistryResult['registrants']) === count($giftRegistry['registrants']) + 1
        );
    }

    /**
     * Testing adding registrant with wrong email to gift registry
     *
     * @magentoApiDataFixture Magento/GiftRegistry/_files/gift_registry_entity_birthday_type.php
     *
     * @throws AuthenticationException
     */
    public function testAddingRegistrantWithWrongEmailToGiftRegistry(): void
    {
        $authHeaders = $this->getCustomerAuthHeaders('customer@example.com', 'password');
        $giftRegistry = $this->getGiftRegistry();
        $registrantData = [
            CustomerInterface::FIRSTNAME => 'John',
            CustomerInterface::LASTNAME => 'Registrant',
            CustomerInterface::EMAIL => 'john@.com',
        ];
        $this->expectExceptionMessage('GraphQL response contains errors: Please enter a valid email address');
        $this->graphQlMutation(
            $this->getMutation($giftRegistry['uid'], $registrantData),
            [],
            '',
            $authHeaders
        );
    }

    /**
     * Testing adding more than allowed registrants to gift registry
     *
     * @magentoApiDataFixture Magento/GiftRegistry/_files/gift_registry_entity_birthday_type.php
     * @magentoConfigFixture default_store magento_giftregistry/general/max_registrant 2
     *
     * @dataProvider registrantDataProvider
     *
     * @param array $registrantData
     *
     * @throws AuthenticationException
     */
    public function testAddingMoreThanAllowedRegistrantsToGiftRegistry(array $registrantData): void
    {
        $authHeaders = $this->getCustomerAuthHeaders('customer@example.com', 'password');
        $giftRegistry = $this->getGiftRegistry();
        $this->expectExceptionMessage('You can\'t add more than 2 recipients for this event.');
        $this->graphQlMutation(
            $this->getMutation($giftRegistry['uid'], $registrantData),
            [],
            '',
            $authHeaders
        );
    }

    /**
     * Providing valid registrant data
     *
     * @return array
     */
    public function registrantDataProvider(): array
    {
        return [
            [
                [
                    CustomerInterface::FIRSTNAME => 'John',
                    CustomerInterface::LASTNAME => 'Registrant',
                    CustomerInterface::EMAIL => 'john@example.com',
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
     * Get gift registry
     *
     * @return array
     *
     * @throws AuthenticationException
     */
    private function getGiftRegistry(): array
    {
        $authHeaders = $this->getCustomerAuthHeaders('customer@example.com', 'password');
        $giftRegistries = $giftRegistry = $this->graphQlQuery($this->getQuery(), [], '', $authHeaders);
        $this->assertNotEmpty($giftRegistries['customer']['gift_registries']);

        return $giftRegistries['customer']['gift_registries'][0];
    }

    /**
     * Get mutation
     *
     * @param string $uid
     * @param array $data
     *
     * @return string
     */
    private function getMutation(string $uid, array $data): string
    {
        return <<<QUERY
mutation {
  addGiftRegistryRegistrants(
  	giftRegistryUid: "{$uid}"
    registrants: [{
      firstname: "{$data[CustomerInterface::FIRSTNAME]}"
      lastname: "{$data[CustomerInterface::LASTNAME]}"
      email: "{$data[CustomerInterface::EMAIL]}"
    }]
) {
  gift_registry {
  	event_name
    registrants {
      uid
      firstname
      lastname
    }
  }
}
}
QUERY;
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
      registrants {
        uid
      }
    }
  }
}
QUERY;
    }
}
