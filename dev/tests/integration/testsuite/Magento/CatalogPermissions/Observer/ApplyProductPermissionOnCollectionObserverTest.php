<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Observer;

use Magento\Catalog\Model\Layer\Search as SearchLayer;
use Magento\CatalogPermissions\Model\Indexer\Category;
use Magento\CatalogPermissions\Model\Indexer\Product;
use Magento\CatalogSearch\Model\Indexer\Fulltext;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Test for \Magento\CatalogPermissions\Observer\ApplyProductPermissionOnCollectionObserverTest class.
 *
 * @magentoDbIsolation disabled
 * @magentoAppArea frontend
 */
class ApplyProductPermissionOnCollectionObserverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Collection
     */
    private $collection;

    /**
     * @var Session
     */
    private $session;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();

        /** @var IndexerRegistry $indexerRegistry */
        $indexerRegistry = $objectManager->create(IndexerRegistry::class);
        $indexerRegistry->get(Category::INDEXER_ID)->reindexAll();
        $indexerRegistry->get(Product::INDEXER_ID)->reindexAll();
        $indexerRegistry->get(Fulltext::INDEXER_ID)->reindexAll();
        $this->collection = $objectManager->create(SearchLayer::class)->getProductCollection();
        $this->session = $objectManager->get(Session::class);
    }

    /**
     * Test search collection size.
     *
     * @param int $customerGroupId
     * @param string $query
     * @param int $expectedSize
     * @dataProvider searchCollectionSizeDataProvider
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/enabled true
     * @magentoDataFixture Magento/CatalogPermissions/_files/category_products_deny.php
     */
    public function testSearchCollectionSize($customerGroupId, $query, $expectedSize)
    {
        $this->session->setCustomerGroupId($customerGroupId);
        $this->collection->addSearchFilter($query);
        $this->collection->setVisibility([3,4]);

        $this->assertEquals($expectedSize, count($this->collection->getItems()));
        $this->assertEquals($expectedSize, $this->collection->getSize());
    }

    /**
     * Test search collection size using partial term match.
     *
     * @param $customerGroupId
     * @param $query
     * @param $expectedSize
     * @dataProvider searchCollectionSizeUsingPartialTermDataProvider
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/enabled true
     * @magentoDataFixture Magento/CatalogPermissions/_files/category_products_deny.php
     */
    public function testSearchCollectionSizeUsingPartialTerm($customerGroupId, $query, $expectedSize)
    {
        if ($this->isSearchEngineElasticsearch()) {
            $this->markTestSkipped('Magento currently does not support partial-term match with Elasticsearch');
        }

        $this->session->setCustomerGroupId($customerGroupId);
        $this->collection->addSearchFilter($query);
        $this->collection->setVisibility([3,4]);

        $this->assertEquals($expectedSize, count($this->collection->getItems()));
        $this->assertEquals($expectedSize, $this->collection->getSize());
    }

    /**
     * Checks if the search engine is currently configured to use any version of Elasticsearch.
     *
     * @return bool
     */
    public function isSearchEngineElasticsearch()
    {
        /** @var ScopeConfigInterface $config */
        $config = Bootstrap::getObjectManager()->get(ScopeConfigInterface::class);
        $searchEngine = $config->getValue('catalog/search/engine');

        return strpos($searchEngine, 'elasticsearch') !== false;
    }

    /**
     * Data provider for testSearchCollectionSize method.
     *
     * @return array
     */
    public function searchCollectionSizeDataProvider()
    {
        return [
            [1, 'simple_deny_122', 0],
            [1, 'simple_allow_122', 1]
        ];
    }

    /**
     * Data provider for testSearchCollectionSizeUsingPartialWord method.
     *
     * @return array
     */
    public function searchCollectionSizeUsingPartialTermDataProvider()
    {
        return [
            [1, 'simple_', 1],
            [0, 'simple_', 0]
        ];
    }
}
