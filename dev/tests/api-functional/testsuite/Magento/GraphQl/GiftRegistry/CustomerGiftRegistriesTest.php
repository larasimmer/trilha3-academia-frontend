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
use Magento\GiftRegistry\Model\Entity as GiftRegistry;
use Magento\GiftRegistry\Model\EntityFactory as GiftRegistryFactory;
use Magento\GiftRegistry\Model\ResourceModel\Entity as GiftRegistryResourceModel;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test for customer gift registries
 */
class CustomerGiftRegistriesTest extends GraphQlAbstract
{
    /**
     * Gift Registry dynamic attributes
     */
    const EVENT_DYNAMIC_ATTRIBUTES = [
        'event_country' => [
            'label' => 'Country',
            'group' => 'EVENT_INFORMATION'
        ],
        'event_date' => [
            'label' => 'Event Date',
            'group' => 'EVENT_INFORMATION'
        ]
    ];

    /**
     * @var CustomerTokenServiceInterface
     */
    private $customerTokenService;

    /**
     * @var GiftRegistryFactory
     */
    private $giftRegistryFactory;

    /**
     * @var GiftRegistryResourceModel
     */
    private $giftRegistryResourceModel;

    /**
     * Set Up
     */
    protected function setUp(): void
    {
        $this->customerTokenService = Bootstrap::getObjectManager()->get(CustomerTokenServiceInterface::class);
        $this->giftRegistryFactory = Bootstrap::getObjectManager()->get(GiftRegistryFactory::class);
        $this->giftRegistryResourceModel = Bootstrap::getObjectManager()->get(GiftRegistryResourceModel::class);
    }

    /**
     * Testing the customer gift registry list
     *
     * @magentoApiDataFixture Magento/GiftRegistry/_files/gift_registry_entity_birthday_type.php
     */
    public function testCustomerGiftRegistry(): void
    {
        $response = $this->graphQlQuery(
            $this->getQuery(),
            [],
            '',
            $this->getCustomerAuthHeaders('customer@example.com', 'password')
        );
        $this->assertNotEmpty($response['customer']['gift_registries']);
        $giftRegistryOutput = $response['customer']['gift_registries'][0];
        /** @var GiftRegistry $giftRegistry */
        $giftRegistry = $this->giftRegistryFactory->create();
        $this->giftRegistryResourceModel->load($giftRegistry, $giftRegistryOutput['uid'], 'url_key');
        $this->assertEquals($giftRegistry->getUrlKey(), $giftRegistryOutput['uid']);
        $this->assertEquals($giftRegistry->getTitle(), $giftRegistryOutput['event_name']);
        $this->assertArrayHasKey('registrants', $giftRegistryOutput);
        $this->assertNotEmpty($giftRegistryOutput['registrants']);
        $registrants = $giftRegistryOutput['registrants'];
        $this->assertEquals('Firstname1', $registrants[0][CustomerInterface::FIRSTNAME]);
        $this->assertEquals('Lastname1', $registrants[0][CustomerInterface::LASTNAME]);
        $this->assertEquals('gift.registrant1@magento.com', $registrants[0][CustomerInterface::EMAIL]);

        $this->assertArrayHasKey('shipping_address', $giftRegistryOutput);
        $this->assertNotEmpty($giftRegistryOutput['shipping_address']);
        $shippingAddress = $giftRegistryOutput['shipping_address'];
        $this->assertEquals('New York', $shippingAddress[AddressInterface::CITY]);
        $this->assertEquals('US', $shippingAddress[AddressInterface::COUNTRY_ID]);
        $this->assertEquals('123456', $shippingAddress[AddressInterface::POSTCODE]);

        $this->assertArrayHasKey('dynamic_attributes', $giftRegistryOutput);
        $this->assertNotEmpty($giftRegistryOutput['dynamic_attributes']);
        $dynamicAttributes = $giftRegistryOutput['dynamic_attributes'];

        foreach ($dynamicAttributes as $dynamicAttribute) {
            $attribute = self::EVENT_DYNAMIC_ATTRIBUTES[$dynamicAttribute['code']];
            $this->assertEquals($dynamicAttribute['label'], $attribute['label']);
            $this->assertEquals($dynamicAttribute['value'], $giftRegistry->getFieldValue($dynamicAttribute['code']));
        }

        $this->assertArrayHasKey('type', $giftRegistryOutput);
        $type = $giftRegistryOutput['type'];

        foreach ($type['dynamic_attributes_metadata'] as $typeDynamicAttribute) {
            $attribute = self::EVENT_DYNAMIC_ATTRIBUTES[$typeDynamicAttribute['code']];
            $this->assertEquals($typeDynamicAttribute['label'], $attribute['label']);
            $this->assertEquals($typeDynamicAttribute['attribute_group'], $attribute['group']);
        }
    }

    /**
     * Testing guest customer request the gift registry
     */
    public function testGuestCannotGetGiftRegistry(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The current customer isn\'t authorized.');
        $this->graphQlQuery($this->getQuery());
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
     * @return string
     */
    private function getQuery(): string
    {
        return <<<QUERY
query {
  customer {
    gift_registries {
      uid
      type {
        uid
        label
        dynamic_attributes_metadata {
          code
          label
          attribute_group
        }
      }
      event_name
      message
      dynamic_attributes {
        label
        code
        group
        value
      }
      registrants {
        uid
        firstname
        lastname
        email
        dynamic_attributes {
          code
          label
          value
        }
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
}
