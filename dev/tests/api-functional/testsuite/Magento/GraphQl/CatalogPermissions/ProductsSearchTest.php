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
 * Test searching products
 */
class ProductsSearchTest extends GraphQlAbstract
{
    /**
     * @var \Magento\TestFramework\ObjectManager
     */
    private $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * @magentoApiDataFixture Magento/CatalogPermissions/_files/category_products_deny.php
     */
    public function testProductsReturnedWhenPermissionsConfigurationDisabled()
    {
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
        $response = $this->graphQlQuery($query);

        $this->assertEquals(2, $response['products']['total_count']);
        $this->assertEquals(2, sizeof($response['products']['items']));
        $this->assertEquals("simple_allow_122", $response['products']['items'][0]['sku']);
        $this->assertEquals("simple_deny_122", $response['products']['items'][1]['sku']);
    }

    /**
     * @magentoConfigFixture default_store catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture default_store catalog/magento_catalogpermissions/grant_catalog_category_view 0
     * @magentoApiDataFixture Magento/CatalogPermissions/_files/category_products_deny.php
     */
    public function testProductsReturnedWhenPermissionsConfigurationEnabled()
    {
        $this->reindexCatalogPermissions();

        $query = <<<QUERY
{
  products(search: "category product"){
    items {
      name
      sku
    }
  }
}
QUERY;
        $response = $this->graphQlQuery($query);

        $this->assertEquals(0, sizeof($response['products']['items']));
    }

    /**
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 1
     * @magentoApiDataFixture Magento/CatalogPermissions/_files/category_products_deny_for_guests.php
     */
    public function testProductsReturnedWhenPermissionsAppliedForGuests()
    {
        $this->reindexCatalogPermissions();

        $query = <<<QUERY
{
  products(search: "Deny category product for guests"){
    items {
      name
      sku
    }
    total_count
  }
}
QUERY;
        $response = $this->graphQlQuery($query);

        $this->assertEquals(0, $response['products']['total_count']);
        $this->assertEquals(0, sizeof($response['products']['items']));
    }

    /**
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 1
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/CatalogPermissions/_files/category_products_deny_for_guests.php
     */
    public function testProductsReturnedWhenPermissionsAppliedForDefaultCustomerGroup()
    {
        $this->reindexCatalogPermissions();

        $query = <<<QUERY
{
  products(search: "Deny category product for guests"){
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

        $this->assertEquals(1, $response['products']['total_count']);
        $this->assertEquals(1, sizeof($response['products']['items']));
        $this->assertEquals("simple_deny_122", $response['products']['items'][0]['sku']);
    }

    /**
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_catalog_category_view 1
     * @magentoApiDataFixture Magento/CatalogPermissions/_files/category_configurable_products_deny_variant_for_guests.php
     */
    public function testConfigurableProductsDenyVariantForGuests()
    {
        $this->reindexCatalogPermissions();

        $configurableSku = 'configurable';

        $query = <<<QUERY
{
  products(search:"{$configurableSku}") {
    items {
      sku
      ... on ConfigurableProduct {
        variants {
          product {
            sku
          }
        }
      }
    }
  }
}
QUERY;

        $response = $this->graphQlQuery($query);
        $this->assertEquals(2, sizeof($response['products']['items']));

        foreach ($response['products']['items'] as $item) {
            if ($item['sku'] == $configurableSku) {
                $this->assertEquals(1, sizeof($response['products']['items'][0]['variants']));
            }
        }
    }

    /**
     * Reindex catalog permissions
     */
    private function reindexCatalogPermissions()
    {
        $appDir = dirname(Bootstrap::getInstance()->getAppTempDir());
        $out = '';
        // phpcs:ignore Magento2.Security.InsecureFunction
        exec("php -f {$appDir}/bin/magento indexer:reindex catalogpermissions_product", $out);
    }
}
