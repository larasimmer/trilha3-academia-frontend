<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\GraphQl\Rma;

use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test for IsRmaEnabled resolver
 */
class IsRmaEnabledTest extends GraphQlAbstract
{
    /**
     * Test enabled RMA
     *
     * @magentoConfigFixture default_store sales/magento_rma/enabled 1
     */
    public function testEnabled(): void
    {
        $query = <<<QUERY
{
  storeConfig {
    returns_enabled
  }
}
QUERY;

        $response = $this->graphQlQuery($query);

        self::assertEquals('enabled', $response['storeConfig']['returns_enabled']);
    }

    /**
     * Test disabled RMA
     *
     * @magentoConfigFixture default_store sales/magento_rma/enabled 0
     */
    public function testDisabled(): void
    {
        $query = <<<QUERY
{
  storeConfig {
    returns_enabled
  }
}
QUERY;

        $response = $this->graphQlQuery($query);

        self::assertEquals('disabled', $response['storeConfig']['returns_enabled']);
    }
}
