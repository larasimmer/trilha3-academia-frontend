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
 * Test for removing registrants from gift registry
 */
class RemoveRegistrantsFromGiftRegistryTest extends GraphQlAbstract
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
     * Testing removing registrants from gift registry
     *
     * @magentoApiDataFixture Magento/GiftRegistry/_files/gift_registry_entity_birthday_type.php
     *
     * @throws AuthenticationException
     */
    public function testRemovingRegistrantsFromGiftRegistry(): void
    {
        $authHeaders = $this->getCustomerAuthHeaders('customer@example.com', 'password');
        $giftRegistry = $this->getGiftRegistry();
        $registrant = end($giftRegistry['registrants']);
        $response = $this->graphQlMutation(
            $this->getMutation($giftRegistry['uid'], $registrant['uid']),
            [],
            '',
            $authHeaders
        );
        $this->assertNotEmpty($response['removeGiftRegistryRegistrants']['gift_registry']);
        $giftRegistryResult = $response['removeGiftRegistryRegistrants']['gift_registry'];
        $this->assertTrue(
            count($giftRegistryResult['registrants']) === count($giftRegistry['registrants']) - 1
        );
    }

    /**
     * Testing removing a wrong registrant id from gift registry
     *
     * @magentoApiDataFixture Magento/GiftRegistry/_files/gift_registry_entity_birthday_type.php
     *
     * @throws AuthenticationException
     */
    public function testRemovingWrongRegistrantIdFromGiftRegistry(): void
    {
        $authHeaders = $this->getCustomerAuthHeaders('customer@example.com', 'password');
        $giftRegistry = $this->getGiftRegistry();
        $wrongUid = 'MTAw';
        $this->expectExceptionMessage(
            'GraphQL response contains errors: The registrant(s) "100" does not exist.'
        );
        $this->graphQlMutation(
            $this->getMutation($giftRegistry['uid'], $wrongUid),
            [],
            '',
            $authHeaders
        );
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
     *
     * @return string
     */
    private function getMutation(string $uid, string $registrantUid): string
    {
        return <<<MUTATION
mutation {
  removeGiftRegistryRegistrants(
  	giftRegistryUid: "{$uid}"
    registrantsUid: ["{$registrantUid}"]
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
