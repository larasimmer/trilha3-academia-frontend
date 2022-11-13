<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\GiftRegistry;

use Magento\Framework\Exception\AuthenticationException;
use Magento\GiftRegistry\Model\Entity as GiftRegistry;
use Magento\GiftRegistry\Model\ResourceModel\Entity as GiftRegistryResourceModel;
use Magento\GiftRegistry\Model\ResourceModel\Entity\Collection as GiftRegistryCollection;
use Magento\GiftRegistry\Model\ResourceModel\Entity\CollectionFactory;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test for customer gift registry
 */
class CustomerGiftRegistryTest extends GraphQlAbstract
{
    /**
     * @var CustomerTokenServiceInterface
     */
    private $customerTokenService;

    /**
     * @var CollectionFactory
     */
    private $giftRegistryCollectionFactory;

    /**
     * Set Up
     */
    protected function setUp(): void
    {
        $this->customerTokenService = Bootstrap::getObjectManager()->get(CustomerTokenServiceInterface::class);
        $this->giftRegistryCollectionFactory = Bootstrap::getObjectManager()->get(CollectionFactory::class);
    }

    /**
     * Testing the customer gift registry
     *
     * @magentoApiDataFixture Magento/GiftRegistry/_files/gift_registry_entity_birthday_type.php
     */
    public function testCustomerGiftRegistry(): void
    {
        $customerId = 1;
        /** @var GiftRegistryCollection $collection */
        $collection = $this->giftRegistryCollectionFactory->create();
        $collection->filterByCustomerId($customerId);
        /** @var GiftRegistry $giftRegistry */
        $giftRegistry = $collection->getFirstItem();
        $response = $this->graphQlQuery(
            $this->getQuery($giftRegistry->getUrlKey()),
            [],
            '',
            $this->getCustomerAuthHeaders('customer@example.com', 'password')
        );
        $this->assertNotEmpty($response['customer']['gift_registry']);
        $giftRegistryOutput = $response['customer']['gift_registry'];
        $this->assertEquals($giftRegistry->getUrlKey(), $giftRegistryOutput['uid']);
        $this->assertEquals($giftRegistry->getTitle(), $giftRegistryOutput['event_name']);
        $this->assertArrayHasKey('registrants', $giftRegistryOutput);
        $this->assertArrayHasKey('shipping_address', $giftRegistryOutput);
        $this->assertArrayHasKey('dynamic_attributes', $giftRegistryOutput);
    }

    /**
     * Testing guest customer request the gift registry
     */
    public function testGuestCannotGetGiftRegistry(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The current customer isn\'t authorized.');
        $this->graphQlQuery($this->getQuery('test-key'));
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
     * Get query
     *
     * @param string $uid
     *
     * @return string
     */
    private function getQuery(string $uid): string
    {
        return <<<QUERY
query {
  customer {
    gift_registry (giftRegistryUid: "{$uid}") {
      uid
      type {
        uid
        label
      }
      event_name
      message
      owner_name
      dynamic_attributes {
        label
        code
        group
        value
      }
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
