<?php
/***
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesArchive\Model\Order\Archive;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProviderInterface;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\SalesArchive\Model\Archive;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Class to test archive grid data provider
 * @magentoAppArea adminhtml
 */
class DataProviderTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var OrderInterfaceFactory */
    private $orderFactory;

    /** @var Archive */
    private $archive;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->orderFactory = $this->objectManager->get(OrderInterfaceFactory::class);
        $this->archive = $this->objectManager->get(Archive::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        $this->archive->removeOrdersFromArchive();

        parent::tearDown();
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Sales/_files/order_new.php
     * @magentoConfigFixture default_store sales/magento_salesarchive/order_statuses pending,closed
     * @return void
     */
    public function testOrderWithPendingStatusMovedToArchiveGrid(): void
    {
        $orderIncrementId = '100000001';
        $ordersDataProvider = $this->createDataProvider('sales_order_grid_data_source', 'main_table.entity_id');
        $archivedOrdersDataProvider = $this->createDataProvider('sales_archive_order_grid_data_source');
        $this->assertTrue(
            $this->isOrderExistsInGrid($ordersDataProvider, $orderIncrementId),
            sprintf('Order with incrementId = %s not found in data provider.', $orderIncrementId)
        );
        $oldRecordsCount = count($ordersDataProvider->getData()['items']);
        $oldArchivedRecordsCount = count($archivedOrdersDataProvider->getData()['items']);
        $this->moveOrderToArchive($orderIncrementId);
        $this->assertEquals(
            1,
            $oldRecordsCount - (int)$ordersDataProvider->getData()['totalRecords'],
            'It seems like the order was not moved to the archive.'
        );
        $this->assertEquals(
            1,
            (int)$archivedOrdersDataProvider->getData()['totalRecords'] - $oldArchivedRecordsCount,
            'It seems like the order was not moved to the archive.'
        );
        $order = $this->orderFactory->create()->loadByIncrementId($orderIncrementId);
        $this->assertTrue(
            $this->checkStatusAvailableInGrid($orderIncrementId, $archivedOrdersDataProvider, $order->getStatus()),
            'Order status not shown in the grid.'
        );
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store sales/magento_salesarchive/order_statuses pending,closed
     * @return void
     */
    public function testOrderWithProcessingStatusMovedToArchiveGrid(): void
    {
        $orderIncrementId = '100000001';
        $archivedOrdersDataProvider = $this->createDataProvider('sales_archive_order_grid_data_source');
        $ordersDataProvider = $this->createDataProvider('sales_order_grid_data_source', 'main_table.entity_id');
        $this->assertTrue(
            $this->isOrderExistsInGrid($ordersDataProvider, $orderIncrementId),
            sprintf('Order with incrementId = %s not found in data provider.', $orderIncrementId)
        );
        $oldRecordsCount = count($ordersDataProvider->getData()['items']);
        $oldArchivedRecordsCount = count($archivedOrdersDataProvider->getData()['items']);
        $this->moveOrderToArchive($orderIncrementId);
        $this->assertEquals(
            0,
            $oldRecordsCount - $ordersDataProvider->getData()['totalRecords'],
            'It seems like the order was not moved to the archive.'
        );
        $this->assertEquals(
            0,
            $archivedOrdersDataProvider->getData()['totalRecords'] - $oldArchivedRecordsCount,
            'It seems like the order was not moved to the archive.'
        );
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Sales/_files/order_with_invoice_shipment_creditmemo.php
     * @magentoConfigFixture default_store sales/magento_salesarchive/order_statuses pending,closed,complete
     * @return void
     */
    public function testOrderWithCompleteStatusMovedToArchiveGrid(): void
    {
        $orderIncrementId = '100000111';
        $ordersDataProvider = $this->createDataProvider('sales_order_grid_data_source', 'main_table.entity_id');
        $archivedOrdersDataProvider = $this->createDataProvider('sales_archive_order_grid_data_source');

        $this->assertTrue(
            $this->isOrderExistsInGrid($ordersDataProvider, $orderIncrementId),
            sprintf('Order with incrementId = %s not found in data provider.', $orderIncrementId)
        );

        $oldRecordsCount = count($ordersDataProvider->getData()['items']);
        $oldArchivedRecordsCount = count($archivedOrdersDataProvider->getData()['items']);

        $this->moveOrderToArchive($orderIncrementId);

        $this->assertEquals(
            1,
            $oldRecordsCount - $ordersDataProvider->getData()['totalRecords'],
            'It seems like the order was not moved to the archive.'
        );
        $this->assertEquals(
            1,
            $archivedOrdersDataProvider->getData()['totalRecords'] - $oldArchivedRecordsCount,
            'It seems like the order was not moved to the archive.'
        );
        $order = $this->orderFactory->create()->loadByIncrementId($orderIncrementId);
        $this->assertTrue(
            $this->checkStatusAvailableInGrid($orderIncrementId, $archivedOrdersDataProvider, $order->getStatus()),
            'Order status not shown in the grid.'
        );
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Sales/_files/order_with_invoice_shipment_creditmemo.php
     * @magentoConfigFixture default_store sales/magento_salesarchive/order_statuses pending,closed,complete
     * @return void
     */
    public function testOrderInvoiceWithCompleteStatusMovedToArchiveGrid(): void
    {
        $dataProvider = $this->createDataProvider('sales_order_invoice_grid_data_source');
        $archiveDataProvider = $this->createDataProvider('sales_archive_order_invoice_grid_data_source');
        $oldRecordsCount = count($dataProvider->getData()['items']);
        $oldArchivedRecordsCount = count($archiveDataProvider->getData()['items']);
        $this->moveOrderToArchive('100000111');
        $this->assertEquals(
            1,
            $oldRecordsCount - $dataProvider->getData()['totalRecords'],
            'It seems like the Order invoice was not moved to the archive.'
        );
        $this->assertEquals(
            1,
            $archiveDataProvider->getData()['totalRecords'] - $oldArchivedRecordsCount,
            'It seems like the Order invoice was not moved to the archive.'
        );
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Sales/_files/order_with_invoice_shipment_creditmemo.php
     * @magentoConfigFixture default_store sales/magento_salesarchive/order_statuses pending,closed,complete
     * @return void
     */
    public function testOrderShipmentWithCompleteStatusMovedToArchiveGrid(): void
    {
        $dataProvider = $this->createDataProvider('sales_order_shipment_grid_data_source');
        $archiveDataProvider = $this->createDataProvider('sales_archive_order_shipment_grid_data_source');
        $oldRecordsCount = count($dataProvider->getData()['items']);
        $oldArchivedRecordsCount = count($archiveDataProvider->getData()['items']);
        $this->moveOrderToArchive('100000111');
        $this->assertEquals(
            1,
            $oldRecordsCount - $dataProvider->getData()['totalRecords'],
            'It seems like the Order shipment was not moved to the archive.'
        );
        $this->assertEquals(
            1,
            $archiveDataProvider->getData()['totalRecords'] - $oldArchivedRecordsCount,
            'It seems like the Order shipment was not moved to the archive.'
        );
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Sales/_files/order_with_invoice_shipment_creditmemo.php
     * @magentoConfigFixture default_store sales/magento_salesarchive/order_statuses pending,closed,complete
     * @return void
     */
    public function testOrderCreditMemoWithCompleteStatusMovedToArchiveGrid(): void
    {
        $dataProvider = $this->createDataProvider('sales_order_view_creditmemo_grid_data_source');
        $archiveDataProvider = $this->createDataProvider('sales_archive_order_creditmemo_grid_data_source');
        $oldRecordsCount = count($dataProvider->getData()['items']);
        $oldArchivedRecordsCount = count($archiveDataProvider->getData()['items']);
        $this->moveOrderToArchive('100000111');
        $this->assertEquals(
            1,
            $oldRecordsCount - $dataProvider->getData()['totalRecords'],
            'It seems like the Order credit memo was not moved to the archive.'
        );
        $this->assertEquals(
            1,
            $archiveDataProvider->getData()['totalRecords'] - $oldArchivedRecordsCount,
            'It seems like the Order credit memo was not moved to the archive.'
        );
    }

    /**
     * Create DataProvider instance.
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @return DataProviderInterface
     */
    private function createDataProvider(
        string $name,
        string $primaryFieldName = 'entity_id',
        string $requestFieldName = 'id'
    ): DataProviderInterface {
        return $this->objectManager->create(
            DataProvider::class,
            [
                'name' => $name,
                'primaryFieldName' => $primaryFieldName,
                'requestFieldName' => $requestFieldName,
            ]
        );
    }

    /**
     * Moves order to archive
     *
     * @param string $incrementId
     * @return void
     */
    private function moveOrderToArchive(string $incrementId): void
    {
        $order = $this->orderFactory->create()->loadByIncrementId($incrementId);
        $this->archive->archiveOrdersById($order->getId());
    }

    /**
     * Checks that order exists in dataProvider
     *
     * @param DataProviderInterface $dataProvider
     * @param string $incrementId
     * @return bool
     */
    private function isOrderExistsInGrid(DataProviderInterface $dataProvider, string $incrementId): bool
    {
        $result = false;
        foreach ($dataProvider->getData()['items'] as $item) {
            if ($item['increment_id'] === $incrementId) {
                $result = true;
                break;
            }
        }
        return $result;
    }

    /**
     * Checks that status is in grid
     *
     * @param string $orderIncrementId
     * @param DataProviderInterface $dataProvider
     * @param string $status
     * @return bool
     */
    private function checkStatusAvailableInGrid(
        string $orderIncrementId,
        DataProviderInterface $dataProvider,
        string $status
    ): bool {
        $result = false;
        foreach ($dataProvider->getData()['items'] as $item) {
            if ($item['increment_id'] === $orderIncrementId && $item['status'] === $status) {
                $result = true;
                break;
            }
        }
        return $result;
    }
}
