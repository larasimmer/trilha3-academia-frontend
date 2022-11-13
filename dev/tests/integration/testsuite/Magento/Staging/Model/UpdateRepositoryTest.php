<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Stdlib\DateTime;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\Operation\Update as OperationUpdate;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Tests Update Repository functionality
 *
 * @magentoDbIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UpdateRepositoryTest extends TestCase
{
    /** @var  UpdateRepository */
    private $repository;

    /** @var  SortOrderBuilder */
    private $sortOrderBuilder;

    /** @var FilterBuilder */
    private $filterBuilder;

    /** @var SearchCriteriaBuilder */
    private $searchCriteriaBuilder;

    /** @var DateTime */
    private $dateTime;

    /** @var Product */
    private $productResource;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->repository = $objectManager->create(UpdateRepository::class);
        $this->searchCriteriaBuilder = $objectManager->create(SearchCriteriaBuilder::class);
        $this->filterBuilder = $objectManager->get(FilterBuilder::class);
        $this->sortOrderBuilder = $objectManager->get(SortOrderBuilder::class);
        $this->dateTime = Bootstrap::getObjectManager()->create(DateTime::class);
        $this->productResource = Bootstrap::getObjectManager()->create(Product::class);
    }

    /**
     * @magentoDataFixture Magento/Staging/_files/staging_update.php
     */
    public function testGetListWithMultipleFiltersAndSorting()
    {
        /**
         * @var $resourceModel \Magento\SalesRule\Model\ResourceModel\Rule
         */
        $resourceModel = Bootstrap::getObjectManager()->create(\Magento\Staging\Model\ResourceModel\Update::class);
        $entityIdField = $resourceModel->getIdFieldName();

        $filter1 = $this->filterBuilder
            ->setField('name')
            ->setValue('%Permanent%')
            ->setConditionType('nlike')
            ->create();
        $filter2 = $this->filterBuilder
            ->setField($entityIdField)
            ->setConditionType('eq')
            ->setValue(300)
            ->create();
        $filter3 = $this->filterBuilder
            ->setField($entityIdField)
            ->setConditionType('eq')
            ->setValue(500)
            ->create();
        $filter4 = $this->filterBuilder
            ->setField('rollback_id')
            ->setConditionType(600)
            ->setValue(500)
            ->create();
        $sortOrder = $this->sortOrderBuilder
            ->setField($entityIdField)
            ->setDirection('DESC')
            ->create();

        $this->searchCriteriaBuilder->addFilters([$filter1, $filter4]);
        $this->searchCriteriaBuilder->addFilters([$filter2, $filter3]);
        $this->searchCriteriaBuilder->addSortOrder($sortOrder);
        $searchCriteria = $this->searchCriteriaBuilder->create();
        /** @var \Magento\Framework\Api\SearchResultsInterface $result */
        $result = $this->repository->getList($searchCriteria);
        $items = $result->getItems();
        $this->assertCount(2, $items);
        $this->assertEquals(500, array_shift($items)->getId());
        $this->assertEquals(300, array_shift($items)->getId());
    }

    /**
     * Checks if changing end time changes staging updated entity row rollback id
     *
     * @magentoDataFixture Magento/Staging/_files/staging_catalog_product_entity.php
     * @magentoDataFixture Magento/Staging/_files/staging_update.php
     */
    public function testSaveUpdateChangedEndTime()
    {
        $update = $this->modifyUpdateStartAndEndTime();
        $this->rescheduleProduct($update);

        $updateRepository = $this->getUpdateRepository();
        $update = $updateRepository->get($update->getId());
        $update->setEndTime($this->dateTime->formatDate('+3 days', true));
        $updateRepository->save($update);

        $row = $this->getProductRowByCreatedIn((int)$update->getId());
        self::assertNotEmpty($row, 'Row is not loaded');
        self::assertEquals(
            $update->getRollbackId(),
            $row['updated_in'],
            'Entity value was updated on full update'
        );
    }

    /**
     * Checks if changing start and end time does not change staging updated entity row rollback id
     *
     * @magentoDataFixture Magento/Staging/_files/staging_catalog_product_entity.php
     * @magentoDataFixture Magento/Staging/_files/staging_update.php
     */
    public function testSaveUpdateChangedStartAndEndTime()
    {
        $update = $this->modifyUpdateStartAndEndTime();
        $oldUpdate = $this->getUpdateRepository()->get(500);

        self::assertEquals($oldUpdate->getMovedTo(), $update->getId(), 'Schedule updated tot correctly');

        $row = $this->getProductRowByCreatedIn((int)$oldUpdate->getId());

        self::assertNotEmpty($row, 'Row is not loaded');
        self::assertEquals(
            $oldUpdate->getRollbackId(),
            $row['updated_in'],
            'Entity value was updated on full update'
        );
    }

    /**
     * @return UpdateInterface
     */
    private function modifyUpdateStartAndEndTime(): UpdateInterface
    {
        $update = $this->getUpdateRepository()->get(500);
        $update->setStartTime($this->dateTime->formatDate('+1 day', true));
        $update->setEndTime($this->dateTime->formatDate('+2 days', true));

        return $this->getUpdateRepository()->save($update);
    }

    /**
     * Creates each time new instance to avoid caching
     *
     * @return UpdateRepositoryInterface
     */
    private function getUpdateRepository(): UpdateRepositoryInterface
    {
        return ObjectManager::getInstance()->create(UpdateRepositoryInterface::class);
    }

    /**
     * Update product schedule update values according to modified update start and end time
     *
     * @param UpdateInterface $update
     * @throws \Exception
     */
    private function rescheduleProduct(UpdateInterface $update): void
    {
        /** @var OperationUpdate $operation */
        $operation = Bootstrap::getObjectManager()->create(OperationUpdate::class);
        /** @var ProductFactory $productFactory */
        $productFactory = Bootstrap::getObjectManager()->create(ProductFactory::class);
        $product = $productFactory->create();
        $this->productResource->load($product, 1);
        $product->setSku('testSku');
        $this->productResource->save($product);
        $operation->execute($product, [
            'origin_in' => 500,
            'created_in' => $update->getId(),
        ]);
    }

    /**
     * @param int $createdIn
     * @return array
     */
    private function getProductRowByCreatedIn(int $createdIn): array
    {
        /** @var EntityMetadataInterface $metadata */
        $metadata = Bootstrap::getObjectManager()->get(MetadataPool::class)->getMetadata(ProductInterface::class);
        $connection = $this->productResource->getConnection();
        $select = $connection->select()
            ->from($metadata->getEntityTable())
            ->where($metadata->getIdentifierField() . ' = ?', 1)
            ->where('created_in = ?', $createdIn)
            ->setPart('disable_staging_preview', true);
        $entity = $connection->fetchRow($select);

        return $entity ?: [];
    }
}
