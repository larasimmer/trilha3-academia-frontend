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
 * Test for sharing a gift registry
 */
class ShareGiftRegistryTest extends GraphQlAbstract
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
     * Testing the customer gift registry share
     *
     * @magentoApiDataFixture Magento/GiftRegistry/_files/gift_registry_entity_birthday_type.php
     *
     * @throws AuthenticationException
     */
    public function testShareCustomerGiftRegistry(): void
    {
        $authHeaders = $this->getCustomerAuthHeaders('customer@example.com', 'password');
        $giftRegistries = $giftRegistry = $this->graphQlQuery($this->getQuery(), [], '', $authHeaders);
        $this->assertNotEmpty($giftRegistries['customer']['gift_registries']);
        $giftRegistry = $giftRegistries['customer']['gift_registries'][0];
        $response = $this->graphQlMutation(
            $this->getShareMutation($giftRegistry['uid']),
            [],
            '',
            $authHeaders
        );
        $this->assertArrayHasKey('shareGiftRegistry', $response);
        $this->assertTrue($response['shareGiftRegistry']['is_shared']);
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
    private function getShareMutation(string $uid): string
    {
        return <<<QUERY
mutation {
  shareGiftRegistry(
    giftRegistryUid: "{$uid}",
    sender: {
      name: "Sender name here",
      message: "Message here"
    },
    invitees: [{
      name: "Inv 1",
      email: "inv1@mail.com"
    }, {
      name: "Inv 2",
      email: "inv2@mail.com"
    }, {
      name: "Inv 3",
      email: "inv3@mail.com"
    }
  ]
  ) {
    is_shared
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
