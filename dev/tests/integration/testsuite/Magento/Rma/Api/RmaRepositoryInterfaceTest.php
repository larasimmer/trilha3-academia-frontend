<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Api;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Rma\Model\Grid as RmaGrid;
use Magento\Rma\Model\Rma;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * @see RmaRepositoryInterface
 */
class RmaRepositoryInterfaceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var RmaRepositoryInterface
     */
    private $repository;

    protected function setUp(): void
    {
        $this->repository = Bootstrap::getObjectManager()->create(RmaRepositoryInterface::class);
    }

    /**
     * @magentoDataFixture Magento/Rma/_files/rmas_for_search.php
     */
    public function testGetList()
    {
        /** @var FilterBuilder $filterBuilder */
        $filterBuilder = Bootstrap::getObjectManager()->create(FilterBuilder::class);

        $filter1 = $filterBuilder->setField(Rma::STATUS)
            ->setValue('status 2')
            ->create();
        $filter2 = $filterBuilder->setField(Rma::STATUS)
            ->setValue('status 3')
            ->create();
        $filter3 = $filterBuilder->setField(Rma::STATUS)
            ->setValue('status 4')
            ->create();
        $filter4 = $filterBuilder->setField(Rma::STATUS)
            ->setValue('status 5')
            ->create();
        $filter5 = $filterBuilder->setField(Rma::CUSTOMER_CUSTOM_EMAIL)
            ->setValue('custom1@custom.net')
            ->create();

        /**@var SortOrderBuilder $sortOrderBuilder */
        $sortOrderBuilder = Bootstrap::getObjectManager()->create(SortOrderBuilder::class);

        /** @var SortOrder $sortOrder */
        $sortOrder = $sortOrderBuilder->setField(Rma::INCREMENT_ID)->setDirection(SortOrder::SORT_DESC)->create();

        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder =  Bootstrap::getObjectManager()->create(SearchCriteriaBuilder::class);

        $searchCriteriaBuilder->addFilters([$filter1, $filter2, $filter3, $filter4]);
        $searchCriteriaBuilder->addFilters([$filter5]);
        $searchCriteriaBuilder->setSortOrders([$sortOrder]);

        $searchCriteriaBuilder->setPageSize(2);
        $searchCriteriaBuilder->setCurrentPage(2);

        $searchCriteria = $searchCriteriaBuilder->create();

        $searchResult = $this->repository->getList($searchCriteria);

        $items = array_values($searchResult->getItems());
        $this->assertCount(1, $items);
        $this->assertEquals('status 3', $items[0][Rma::STATUS]);
    }

    /**
     * RMA grid data correctness test.
     *
     * @magentoDataFixture Magento/Rma/_files/rma.php
     */
    public function testRmaGridData(): void
    {
        $rmaGrid = $this->getRmaGrid();

        $this->assertNotNull($rmaGrid->getOrderDate(), 'Order Date is missing for RMA grid.');
        $this->assertNotNull($rmaGrid->getCustomerName(), 'Customer Name is missing for RMA grid.');
    }

    /**
     * RMA items data correctness test.
     *
     * @magentoDataFixture Magento/Rma/_files/rma.php
     */
    public function testRmaItemsData(): void
    {
        $rmaItems = $this->getRmaItems();

        foreach ($rmaItems as $rmaItem) {
            $this->assertNotNull($rmaItem->getProductName(), 'Product Name is missing for RMA item.');
            $this->assertNotNull($rmaItem->getProductSku(), 'Product SKU is missing for RMA item.');
            $this->assertNotNull($rmaItem->getProductAdminName(), 'ProductAdminName is missing for RMA item.');
            $this->assertNotNull($rmaItem->getProductAdminSku(), 'Product Admin SKU is missing for RMA item.');
        }
    }

    /**
     * Returns RMA grid.
     *
     * @return RmaGrid
     */
    private function getRmaGrid()
    {
        /** @var RmaGrid $grid */
        $rmaGrid = Bootstrap::getObjectManager()->create(RmaGrid::class);
        $rmaGrid->load(1, 'increment_id');

        return $rmaGrid;
    }

    /**
     * Returns RMA items.
     *
     * @return array|Data\ItemInterface[]|mixed
     */
    private function getRmaItems()
    {
        $rma = $this->getRma();

        return $rma->getItems();
    }

    /**
     * Returns RMA instance.
     *
     * @return Rma
     */
    private function getRma()
    {
        /** @var Rma $rma */
        $rma = Bootstrap::getObjectManager()->create(Rma::class);
        $rma->load(1, 'increment_id');

        return $rma;
    }
}
