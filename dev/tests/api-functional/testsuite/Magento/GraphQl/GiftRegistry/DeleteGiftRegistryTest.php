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
 * Test for deleting a gift registry
 */
class DeleteGiftRegistryTest extends GraphQlAbstract
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
     * @throws AuthenticationException
     */
    public function testCreateCustomerGiftRegistry(): void
    {
        $authHeaders = $this->getCustomerAuthHeaders('customer@example.com', 'password');
        $giftRegistries = $this->graphQlQuery($this->getQuery(), [], '', $authHeaders);
        $this->assertNotEmpty($giftRegistries['customer']['gift_registries']);
        $giftRegistry = $giftRegistries['customer']['gift_registries'][0];
        $response = $this->graphQlMutation(
            $this->getDeleteMutation($giftRegistry['uid']),
            [],
            '',
            $authHeaders
        );
        $this->assertArrayHasKey('removeGiftRegistry', $response);
        $this->assertTrue($response['removeGiftRegistry']['success']);
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
     *
     * @return string
     */
    private function getDeleteMutation(string $uid): string
    {
        return <<<QUERY
mutation {
  removeGiftRegistry(giftRegistryUid: "{$uid}") {
    success
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
    }
  }
}
QUERY;
    }
}
