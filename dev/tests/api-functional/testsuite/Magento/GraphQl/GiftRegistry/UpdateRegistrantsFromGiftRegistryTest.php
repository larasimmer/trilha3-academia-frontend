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
 * Test for updating registrants from gift registry
 */
class UpdateRegistrantsFromGiftRegistryTest extends GraphQlAbstract
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
     * Testing updating registrants from gift registry
     *
     * @magentoApiDataFixture Magento/GiftRegistry/_files/gift_registry_entity_birthday_type.php
     *
     * @dataProvider giftRegistryRegistrantDataProvider
     *
     * @param array $data
     *
     * @throws AuthenticationException
     */
    public function testUpdatingRegistrantsFromGiftRegistry(array $data): void
    {
        $authHeaders = $this->getCustomerAuthHeaders('customer@example.com', 'password');
        $giftRegistry = $this->getGiftRegistry();
        $registrant = $giftRegistry['registrants'][0];
        $response = $this->graphQlMutation(
            $this->getMutation($giftRegistry['uid'], $registrant['uid'], $data),
            [],
            '',
            $authHeaders
        );
        $this->assertNotEmpty($response['updateGiftRegistryRegistrants']['gift_registry']);
        $giftRegistryResult = $response['updateGiftRegistryRegistrants']['gift_registry'];

        foreach ($giftRegistryResult['registrants'] as $updatedRegistrant) {
            if ($updatedRegistrant['uid'] === $registrant['uid']) {
                $this->assertEquals(
                    $data[CustomerInterface::FIRSTNAME],
                    $updatedRegistrant[CustomerInterface::FIRSTNAME]
                );
                $this->assertEquals(
                    $data[CustomerInterface::LASTNAME],
                    $updatedRegistrant[CustomerInterface::LASTNAME]
                );
                $this->assertEquals($data[CustomerInterface::EMAIL], $updatedRegistrant[CustomerInterface::EMAIL]);
            }
        }
    }

    /**
     * Providing test gift registry data
     *
     * @return array
     */
    public function giftRegistryRegistrantDataProvider(): array
    {
        return [
            [
                [
                    CustomerInterface::FIRSTNAME => 'John',
                    CustomerInterface::LASTNAME => 'Doe',
                    CustomerInterface::EMAIL => 'john-doe@example.com',
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
     * @param string $registrantUid
     * @param array $data
     *
     * @return string
     */
    private function getMutation(string $uid, string $registrantUid, array $data): string
    {
        return <<<MUTATION
mutation {
  updateGiftRegistryRegistrants(
  	giftRegistryUid: "{$uid}"
    registrants: [{
      gift_registry_registrant_uid: "{$registrantUid}"
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
      email
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
      registrants {
        uid
      }
    }
  }
}
QUERY;
    }
}
