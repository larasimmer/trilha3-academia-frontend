<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\ElasticsearchCatalogPermissionsGraphQl;

use Magento\CatalogPermissions\Model\Indexer\Product as IndexerProduct;
use Magento\CatalogPermissions\Model\Indexer\Category as IndexerCategory;
use Magento\CatalogSearch\Model\Indexer\Fulltext as IndexerSearch;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Indexer\Model\Indexer;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test Get Products With Enabled Catalog Permissions
 */
class GetProductsWithCatalogPermissionsTest extends GraphQlAbstract
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
     * @magentoDataFixture Magento/CatalogPermissions/_files/category_products_guest_deny.php
     * @magentoDbIsolation disabled
     */
    public function testSearchProductsAsGuest()
    {
        $this->makeReindex();
        $product = 'product';
        $query
            = <<<QUERY
        {
          products(search: "$product") {
            items {
              sku
              name
            }
          }
        }

QUERY;

        $response = $this->graphQlQuery($query);

        $this->assertEquals(1, count($response['products']['items']));
        $this->assertContains('simple_allow_122', $response['products']['items'][0]);
    }

    /**
     * @magentoConfigFixture admin_store catalog/magento_catalogpermissions/enabled true
     * @magentoDataFixture Magento/CatalogPermissions/_files/category_products_deny.php
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoDbIsolation disabled
     */
    public function testSearchProductsAsLoggedInUser()
    {
        $this->makeReindex();
        $product = 'product';
        $query
            = <<<QUERY
        {
          products(search: "$product") {
            items {
              sku
              name
            }
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
        $this->assertEquals(1, count($response['products']['items']));
        $this->assertContains('simple_allow_122', $response['products']['items'][0]);
    }

    /**
     * Clean index
     * @throws \Exception
     */
    private function makeReindex()
    {
        /** @var $indexer \Magento\Framework\Indexer\IndexerInterface */
        $indexer = $this->objectManager->create(Indexer::class);
        $indexer->load(IndexerProduct::INDEXER_ID);
        $indexer->reindexAll();
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
