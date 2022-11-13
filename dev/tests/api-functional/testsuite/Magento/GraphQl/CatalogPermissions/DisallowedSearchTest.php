<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\CatalogPermissions;

use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\GraphQl\GetCustomerAuthenticationHeader;

/**
 * Test searching products when configuration disallows it
 */
class DisallowedSearchTest extends GraphQlAbstract
{
    /**
     * @var \Magento\TestFramework\ObjectManager
     */
    private $objectManager;

    /**
     * Set Up
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 1
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/CatalogPermissions/_files/category_products_deny.php
     */
    public function testProductsReturnedWhenPermissionsAppliedForDefaultCustomerGroup()
    {
        $this->reindexCatalogPermissions();

        $query = <<<QUERY
{
  products(search: "Allow category product"){
    items {
      name
      sku
    }
    total_count
  }
}
QUERY;

        $currentEmail = 'customer@example.com';
        $currentPassword = 'password';
        $response = $this->graphQlQuery(
            $query,
            [],
            '',
            $this->objectManager->get(GetCustomerAuthenticationHeader::class)->execute($currentEmail, $currentPassword)
        );

        $this->assertEquals(1, sizeof($response['products']['items']));
        $this->assertEquals("simple_allow_122", $response['products']['items'][0]['sku']);
    }

    /**
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/deny_catalog_search 1,2
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/CatalogPermissions/_files/category_products_deny.php
     */
    public function testExceptionReceivedWhenSearchDisallowed()
    {
        $this->reindexCatalogPermissions();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Product search has been disabled.');

        $query = <<<QUERY
{
  products(search: "Allow category product"){
    items {
      name
      sku
    }
    total_count
  }
}
QUERY;

        $currentEmail = 'customer@example.com';
        $currentPassword = 'password';
        $this->graphQlQuery(
            $query,
            [],
            '',
            $this->objectManager->get(GetCustomerAuthenticationHeader::class)->execute($currentEmail, $currentPassword)
        );
    }

    /**
     * Reindex catalog permissions
     */
    private function reindexCatalogPermissions()
    {
        $appDir = dirname(Bootstrap::getInstance()->getAppTempDir());
        $out = '';
        // phpcs:ignore Magento2.Security.InsecureFunction
        exec("php -f {$appDir}/bin/magento indexer:reindex catalogpermissions_category", $out);
    }
}
