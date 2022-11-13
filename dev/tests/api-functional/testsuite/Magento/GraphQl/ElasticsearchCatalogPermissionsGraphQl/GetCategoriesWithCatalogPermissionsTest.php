<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\ElasticsearchCatalogPermissionsGraphQl;

use Magento\CatalogPermissions\Model\Indexer\Category as IndexerCategory;
use Magento\CatalogSearch\Model\Indexer\Fulltext as IndexerSearch;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Indexer\Model\Indexer;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Tests for Category Catalog Permissions functionality
 */
class GetCategoriesWithCatalogPermissionsTest extends GraphQlAbstract
{
    /** @var  CustomerTokenServiceInterface */
    private $customerTokenService;

    /** @var ObjectManager */
    private $objectManager;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->customerTokenService = $this->objectManager->get(CustomerTokenServiceInterface::class);
    }

    /**
     * @magentoConfigFixture admin_store catalog/magento_catalogpermissions/enabled true
     * @magentoDataFixture Magento/CatalogPermissions/_files/category_permissions_for_guest.php
     * @magentoDbIsolation disabled
     * @expectedException \Magento\TestFramework\TestCase\GraphQl\ResponseContainsErrorsException
     * @expectedExceptionMessage GraphQL response contains errors: Category doesn't exist
     */
    public function testCategoryPermissionsHidden()
    {
        $this->markTestIncomplete('Replace with CategoryList query');
        $this->makeReindex();

        $categoryId = 4;
        $query
            = <<<QUERY
        {
            category(id: $categoryId){
                id
                name
            }
        }
QUERY;
        $this->graphQlQuery($query);
    }

    /**
     * @magentoConfigFixture admin_store catalog/magento_catalogpermissions/enabled true
     * @magentoDataFixture Magento/CatalogPermissions/_files/category_permissions_for_logged_in.php
     * @magentoDbIsolation disabled
     * @expectedException \Magento\TestFramework\TestCase\GraphQl\ResponseContainsErrorsException
     * @expectedExceptionMessage GraphQL response contains errors: Not enough permissions to access category
     */
    public function testCategoryPermissionsNotAvailableForNotLoggedInCustomerGroup()
    {
        $this->markTestIncomplete('Replace with CategoryList query');

        $this->makeReindex();
        $categoryId = 3;
        $query
            = <<<QUERY
        {
            category(id: $categoryId){
                id
                name
            }
        }
QUERY;
        $this->graphQlQuery($query);
    }

    /**
     * @magentoConfigFixture admin_store catalog/magento_catalogpermissions/enabled true
     * @magentoDataFixture Magento/CatalogPermissions/_files/category_permissions_for_logged_in.php
     * @magentoDbIsolation disabled
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     */
    public function testCategoryPermissionsAvailableForLoggedInCustomerGroup()
    {
        $this->makeReindex();
        $categoryId = 3;
        $assertionMap = [
            'category' => [
                'id' => 3,
                'name' => 'Allow category'
            ]
        ];
        $query
            = <<<QUERY
        {
            category(id: $categoryId){
                id
                name
            }
}
QUERY;
        // get customer ID token
        /** @var \Magento\Integration\Api\CustomerTokenServiceInterface $customerTokenService */
        $customerTokenService = $this->objectManager->create(
            \Magento\Integration\Api\CustomerTokenServiceInterface::class
        );
        $customerToken = $customerTokenService->createCustomerAccessToken('customer@example.com', 'password');

        $headerMap = ['Authorization' => 'Bearer ' . $customerToken];
        $response = $this->graphQlQuery($query, [], '', $headerMap);
        $this->assertResponseFields($response, $assertionMap);
    }

    /**
     * @magentoConfigFixture admin_store catalog/magento_catalogpermissions/enabled true
     * @magentoDataFixture Magento/CatalogPermissions/_files/child_category_permissions_for_logged_in.php
     * @magentoDbIsolation disabled
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     */
    public function testChildCategoryPermissionsAvailableForLoggedInCustomerGroup()
    {
        $this->makeReindex();
        $categoryId = 5;
        $assertionMap = [
            'category' => [
                'id' => 5,
                'name' => 'Allow child category'
            ]
        ];
        $query
            = <<<QUERY
        {
            category(id: $categoryId){
                id
                name
            }
        }
QUERY;
        // get customer ID token
        /** @var \Magento\Integration\Api\CustomerTokenServiceInterface $customerTokenService */
        $customerTokenService = $this->objectManager->create(
            \Magento\Integration\Api\CustomerTokenServiceInterface::class
        );
        $customerToken = $customerTokenService->createCustomerAccessToken('customer@example.com', 'password');

        $headerMap = ['Authorization' => 'Bearer ' . $customerToken];
        $response = $this->graphQlQuery($query, [], '', $headerMap);
        $this->assertResponseFields($response, $assertionMap);
    }

    /**
     * Clean index
     * @throws \Exception
     */
    private function makeReindex()
    {
        /** @var $indexer \Magento\Framework\Indexer\IndexerInterface */
        $indexer = $this->objectManager->create(Indexer::class);
        $indexer->load(IndexerCategory::INDEXER_ID);
        $indexer->reindexAll();
        $indexer->load(IndexerSearch::INDEXER_ID);
        $indexer->reindexAll();
    }

    /**
     * Tear down after tests
     */
    public static function tearDownAfterClass(): void
    {
        $config = ObjectManager::getInstance()->get(Config::class);
        $config->saveConfig(\Magento\CatalogPermissions\App\ConfigInterface::XML_PATH_ENABLED, 0);
    }
}
