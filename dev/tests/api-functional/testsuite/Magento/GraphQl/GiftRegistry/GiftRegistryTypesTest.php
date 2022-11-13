<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\GiftRegistry;

use Exception;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test for fetching the gift registry types
 */
class GiftRegistryTypesTest extends GraphQlAbstract
{
    /**
     * Default gift registry types
     *
     * @var string[]
     */
    private $defaultTypes = [
        1 => 'Birthday',
        2 => 'Baby Registry',
        3 => 'Wedding'
    ];

    /**
     * Testing the customer gift registry type list
     *
     * @throws Exception
     */
    public function testGettingTheGiftRegistryTypes(): void
    {
        $giftRegistryTypes = $this->graphQlQuery($this->getQuery());
        $this->assertArrayHasKey('giftRegistryTypes', $giftRegistryTypes);

        foreach ($giftRegistryTypes['giftRegistryTypes'] as $giftRegistryType) {
            $id = base64_decode($giftRegistryType['uid']);
            $this->assertArrayHasKey($id, $this->defaultTypes);
            $this->assertEquals($this->defaultTypes[$id], $giftRegistryType['label']);
        }
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
  giftRegistryTypes {
    uid
    label
    dynamic_attributes_metadata {
      code
      label
    }
  }
}
QUERY;
    }
}
