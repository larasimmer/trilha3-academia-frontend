<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\GiftRegistry;

use Magento\TestFramework\TestCase\GraphQlAbstract;

class SearchTest extends GraphQlAbstract
{
    /**
     * Gift registry search valid response
     */
    private const VALID_RESPONSE = [
        'gift_registry_uid' => 'gift_registry_birthday_type_url',
        'event_title' => 'Gift Registry Birthday Type',
        'type' => 'Birthday',
        'name' => 'Firstname1 Lastname1'
    ];

    /**
     * Searching gift registries by registrant's email
     *
     * @magentoApiDataFixture Magento/GiftRegistry/_files/gift_registry_entity_birthday_type.php
     */
    public function testSearchGiftRegistryByRegistrantEmail(): void
    {
        $response = $this->graphQlQuery($this->searchByEmailQuery('gift.registrant1@magento.com'));
        $this->assertCount(1, $response['giftRegistryEmailSearch']);
        $this->validateResponse($response['giftRegistryEmailSearch'][0]);
    }

    /**
     * Searching gift registries by a missing registrant's email
     *
     * @magentoApiDataFixture Magento/GiftRegistry/_files/gift_registry_entity_birthday_type.php
     */
    public function testSearchGiftRegistryByMissingRegistrantEmail(): void
    {
        $response = $this->graphQlQuery($this->searchByEmailQuery('unknown@example.com'));
        $this->assertCount(0, $response['giftRegistryEmailSearch']);
    }

    /**
     * Searching by Gift Registry URL Key
     *
     * @magentoApiDataFixture Magento/GiftRegistry/_files/gift_registry_entity_birthday_type.php
     */
    public function testSearchGiftRegistryByUrlKey(): void
    {
        $response = $this->graphQlQuery($this->searchByIdQuery('gift_registry_birthday_type_url'));
        $this->assertCount(1, $response['giftRegistryIdSearch']);
        $this->validateResponse($response['giftRegistryIdSearch'][0]);
    }

    /**
     * Searching by ID with a wrong URL key
     *
     * @magentoApiDataFixture Magento/GiftRegistry/_files/gift_registry_entity_birthday_type.php
     */
    public function testSearchIdWithWrongUrlKey(): void
    {
        $response = $this->graphQlQuery($this->searchByIdQuery('unknown_gift_registry_test_url_key'));
        $this->assertCount(0, $response['giftRegistryIdSearch']);
    }

    /**
     * Searching a gift registry by type, specifying the type
     *
     * @magentoApiDataFixture Magento/GiftRegistry/_files/gift_registry_entity_birthday_type.php
     */
    public function testSearchTypeSpecifyingWithType(): void
    {
        $response = $this->graphQlQuery(
            $this->searchBytypeQuery('Firstname1', 'Lastname1', 'MQ==')
        );
        $this->assertCount(1, $response['giftRegistryTypeSearch']);
        $this->validateResponse($response['giftRegistryTypeSearch'][0]);
    }

    /**
     * Searching by some non existing registrants
     *
     * @magentoApiDataFixture Magento/GiftRegistry/_files/gift_registry_entity_birthday_type.php
     */
    public function testSearchTypeNonExistingRegistrantsResolver(): void
    {
        $response = $this->graphQlQuery(
            $this->searchBytypeQuery('Unknown-First', 'Unknown-Last', 'MQ==')
        );
        $this->assertCount(0, $response['giftRegistryTypeSearch']);
    }

    /**
     * Searching a gift registry by type, without specifying the type
     *
     * @magentoApiDataFixture Magento/GiftRegistry/_files/gift_registry_entity_birthday_type.php
     */
    public function testSearchTypeWithoutTypeResolver(): void
    {
        $response = $this->graphQlQuery(
            $this->searchBytypeQuery('Firstname1', 'Lastname1')
        );
        $this->assertCount(1, $response['giftRegistryTypeSearch']);
        $this->validateResponse($response['giftRegistryTypeSearch'][0]);
    }

    /**
     * Validating the response
     *
     * @param array $response
     */
    private function validateResponse(array $response): void
    {
        foreach (self::VALID_RESPONSE as $key => $value) {
            $this->assertArrayHasKey($key, $response);
            $this->assertEquals($value, (string) $response[$key]);
        }
    }

    /**
     * Search by email query
     *
     * @param string $email
     *
     * @return string
     */
    private function searchByEmailQuery(string $email): string
    {
        return <<<QUERY
query {
  giftRegistryEmailSearch(email: "$email") {
    gift_registry_uid
    event_title
	type
    name
    location
  }
}
QUERY;
    }

    /**
     * Search by ID query
     *
     * @param string $urlKey
     *
     * @return string
     */
    private function searchByIdQuery(string $urlKey): string
    {
        return <<<QUERY
query {
  giftRegistryIdSearch (giftRegistryUid: "$urlKey") {
    gift_registry_uid
    event_title
	type
    name
    location
  }
}
QUERY;
    }

    /**
     * Search by type query
     *
     * @param string $firstName
     * @param string $lastName
     * @param string|null $typeUid
     *
     * @return string
     */
    private function searchByTypeQuery(string $firstName, string $lastName, ?string $typeUid = null): string
    {
        $typeUid = $typeUid ? "\"$typeUid\"" : 'null';
        return <<<QUERY
query {
giftRegistryTypeSearch (firstName: "$firstName", lastName: "$lastName", giftRegistryTypeUid: $typeUid) {
    gift_registry_uid
    event_title
	type
    name
    location
    event_date
  }
}
QUERY;
    }
}
