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
 * Test categories querying
 */
class CategoriesQueryTest extends GraphQlAbstract
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
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 0
     * @magentoApiDataFixture Magento/CatalogPermissions/_files/category_products_deny.php
     */
    public function testCategoriesReturnedWhenPermissionsConfigurationDisabled()
    {
        $query = <<<QUERY
{
  categories(filters: {name: {match: "category"}}){
    items {
      id
      name
      product_count
    }
  }
}
QUERY;
        $response = $this->graphQlQuery($query);

        $this->assertCount(3, $response['categories']['items']);
    }

    /**
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 1
     * @magentoApiDataFixture Magento/CatalogPermissions/_files/category_products_deny.php
     */
    public function testCategoriesReturnedForGuest()
    {
        $this->reindexCatalogPermissions();

        $query = <<<QUERY
{
  categories(filters: {name: {match: "category"}}){
    items {
      id
      name
      product_count
    }
  }
}
QUERY;
        $response = $this->graphQlQuery($query);

        $this->assertCount(0, $response['categories']['items']);
    }

    /**
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 1
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/CatalogPermissions/_files/category_products_deny.php
     */
    public function testCategoriesReturnedForCustomer()
    {
        $this->reindexCatalogPermissions();

        $query = <<<QUERY
{
  categories(filters: {name: {match: "category"}}){
    items {
      id
      name
      product_count
    }
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

        $this->assertCount(1, $response['categories']['items']);
    }

    /**
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_catalog_category_view 2
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_catalog_category_view_groups 1
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/CatalogPermissions/_files/category_products.php
     */
    public function testCategoriesReturnedForCustomerGroupsAllowedByConfiguration()
    {
        $this->reindexCatalogPermissions();

        $query = <<<QUERY
{
  categories(filters: {ids: {eq: "3"}}){
    total_count
    items {
        children_count
        products {
            items {
                id
            }
        }
    }
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

        $this->assertCount(1, $response['categories']['items']);
        $this->assertCount(2, $response['categories']['items'][0]['products']['items']);
    }

    /**
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_catalog_category_view 2
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_catalog_category_view_groups 2
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/CatalogPermissions/_files/category_products.php
     */
    public function testCategoriesReturnedForCustomerGroupsDeniedByConfiguration()
    {
        $this->reindexCatalogPermissions();

        $query = <<<QUERY
{
  categories(filters: {ids: {eq: "3"}}){
    total_count
    items {
        children_count
        products {
            items {
                id
            }
        }
    }
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

        $this->assertEmpty($response['categories']['items']);
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
