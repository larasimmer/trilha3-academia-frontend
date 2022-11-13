<?php
/***
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\VersionsCms\Model\Hierarchy;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\ObjectManager;
use Magento\VersionsCms\Api\Data\HierarchyNodeSearchResultsInterface;
use Magento\VersionsCms\Model\Hierarchy\NodeRepository;

/**
 * @magentoAppIsolation enabled
 */
class NodeRepositoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var  NodeRepository */
    private $repository;

    /** @var  SortOrderBuilder */
    private $sortOrderBuilder;

    /** @var FilterBuilder */
    private $filterBuilder;

    /** @var SearchCriteriaBuilder */
    private $searchCriteriaBuilder;

    protected function setUp(): void
    {
        $objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $this->repository = $objectManager->create(NodeRepository::class);
        $this->searchCriteriaBuilder = $objectManager->create(
            \Magento\Framework\Api\SearchCriteriaBuilder::class
        );
        $this->filterBuilder = $objectManager->get(
            \Magento\Framework\Api\FilterBuilder::class
        );
        $this->sortOrderBuilder = $objectManager->get(
            \Magento\Framework\Api\SortOrderBuilder::class
        );
    }

    /**
     * @magentoDataFixture Magento/VersionsCms/_files/hierarchy_nodes.php
     */
    public function testGetListWithMultipleFiltersAndSorting()
    {
        $objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        /** @var Store $store */
        $store = $objectManager->create(Store::class);
        $store->load('second_store', 'code');

        if (!$store->getId()) {
            $this->fail('Cannot load second store');
        }

        $filter1 = $this->filterBuilder
            ->setField('scope_id')
            ->setValue(1)
            ->create();
        $filter2 = $this->filterBuilder
            ->setField('level')
            ->setValue(4)
            ->create();

        /** @TODO: MAGETWO-56389 */
        $filter4 = $this->filterBuilder
            ->setField('xpath')
            ->setValue('%class=test%')
            ->setConditionType('like')
            ->create();
        $sortOrder = $this->sortOrderBuilder
            ->setField('sort_order')
            ->setDirection('DESC')
            ->create();

        $this->searchCriteriaBuilder->addFilters([$filter1, $filter2]);
        $this->searchCriteriaBuilder->addFilters([$filter4]);
        $this->searchCriteriaBuilder->addSortOrder($sortOrder);
        $searchCriteria = $this->searchCriteriaBuilder->create();
        /** @var \Magento\VersionsCms\Api\Data\HierarchyNodeSearchResultsInterface $result */
        $result = $this->repository->getList($searchCriteria);
        $items = $result->getItems();
        $this->assertCount(2, $items);
        $this->assertEquals('third', array_shift($items)['identifier']);
        $this->assertEquals('first', array_shift($items)['identifier']);
    }

    /**
     * @magentoDataFixture Magento/VersionsCms/_files/hierarcy_nodes_with_pages_on_different_websites.php
     * @magentoDataFixture Magento/VersionsCms/_files/hierarcy_nodes_with_pages_on_default_store.php
     * @param string $storeCode
     * @param array $expected
     * @dataProvider storeFilterDataProvider
     */
    public function testStoreFilter(string $storeCode, array $expected): void
    {
        $objectManager = ObjectManager::getInstance();
        /** @var StoreManagerInterface $storeManager */
        $storeManager = $objectManager->get(StoreManagerInterface::class);
        $store = $storeManager->getStore($storeCode);
        $this->searchCriteriaBuilder->addFilter('store_id', $store->getId());
        $this->searchCriteriaBuilder->addFilter('scope_id', $store->getId());
        $searchCriteria = $this->searchCriteriaBuilder->create();
        /** @var HierarchyNodeSearchResultsInterface $result */
        $result = $this->repository->getList($searchCriteria);
        $actual = [];
        foreach ($result->getItems() as $item) {
            $actual[] = $item['request_url'] ?? null;
        }
        $this->assertEqualsCanonicalizing(
            $expected,
            $actual
        );
    }

    /**
     * @return array[]
     */
    public function storeFilterDataProvider(): array
    {
        return [
            [
                'default',
                [
                    null,
                    'default_store_main_node',
                    'default_store_main_node/page_design_blank',
                ]
            ],
            [
                'test',
                [
                    null,
                    'main',
                    'main/page100',
                ]
            ]
        ];
    }
}
