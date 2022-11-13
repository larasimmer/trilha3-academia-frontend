<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesArchive\Controller\Adminhtml\Archive;

use Magento\Sales\Controller\Adminhtml\Order\ExportBase;
use Magento\SalesArchive\Model\ArchivalList;
use Magento\TestFramework\SalesArchive\Model\GetEntityData;

/**
 * Tests for archive orders/invoices/shipments/credit memos export via admin grids.
 */
class ExportTest extends ExportBase
{
    /**
     * @var GetEntityData
     */
    private $getEntityData;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->getEntityData = $this->_objectManager->get(GetEntityData::class);
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoAppArea adminhtml
     * @magentoConfigFixture general/locale/timezone America/Chicago
     * @magentoConfigFixture test_website general/locale/timezone America/Adak
     * @magentoDataFixture Magento/SalesArchive/_files/archived_order_on_second_website.php
     * @dataProvider exportDataProvider
     * @param string $entityCode
     * @param string $format
     * @param array $params
     * @param array $fieldsMap
     * @return void
     */
    public function testExport(
        string $entityCode,
        string $format,
        array $params,
        array $fieldsMap
    ): void {
        $response = $this->dispatchExport($this->getExportUrl($format, null), $params);
        $exportedArchiveEntities = $this->parseResponse($format, $response);
        $archiveEntity = $this->getArchiveEntityByIncrementId($entityCode, '200000001');
        $exportedArchiveEntity = reset($exportedArchiveEntities);
        $this->assertNotFalse($exportedArchiveEntity);
        foreach ($fieldsMap as $field => $exportedField) {
            $this->assertEquals(
                $this->prepareDate($archiveEntity[$field], 'America/Chicago'),
                $exportedArchiveEntity[$exportedField]
            );
        }
    }

    /**
     * @return array
     */
    public function exportDataProvider(): array
    {
        return [
            'archive_order_grid_in_csv' => [
                'entity_code' => ArchivalList::ORDER,
                'format' => ExportBase::CSV_FORMAT,
                'params' => [
                    'namespace' => 'sales_archive_order_grid',
                    'filters' => ['increment_id' => '200000001'],
                ],
                'field_map' => ['created_at' => 'Purchase Date']
            ],
            'archive_order_grid_in_xml' => [
                'entity_code' => ArchivalList::ORDER,
                'format' => ExportBase::XML_FORMAT,
                'params' => [
                    'namespace' => 'sales_archive_order_grid',
                    'filters' => ['increment_id' => '200000001'],
                ],
                'field_map' => ['created_at' => 'Purchase Date']
            ],
            'archive_invoice_grid_in_csv' => [
                'entity_code' => ArchivalList::INVOICE,
                'format' => ExportBase::CSV_FORMAT,
                'params' => [
                    'namespace' => 'sales_archive_order_invoice_grid',
                    'filters' => ['order_increment_id' => '200000001'],
                ],
                'field_map' => ['created_at' => 'Invoice Date', 'order_created_at' => 'Order Date']
            ],
            'archive_invoice_grid_in_xml' => [
                'entity_code' => ArchivalList::INVOICE,
                'format' => ExportBase::XML_FORMAT,
                'params' => [
                    'namespace' => 'sales_archive_order_invoice_grid',
                    'filters' => ['order_increment_id' => '200000001'],
                ],
                'field_map' => ['created_at' => 'Invoice Date', 'order_created_at' => 'Order Date']
            ],
            'archive_creditmemo_grid_in_csv' => [
                'entity_code' => ArchivalList::CREDITMEMO,
                'format' => ExportBase::CSV_FORMAT,
                'params' => [
                    'namespace' => 'sales_archive_order_creditmemo_grid',
                    'filters' => ['order_increment_id' => '200000001'],
                ],
                'field_map' => ['created_at' => 'Created', 'order_created_at' => 'Order Date']
            ],
            'archive_creditmemo_grid_in_xml' => [
                'entity_code' => ArchivalList::CREDITMEMO,
                'format' => ExportBase::XML_FORMAT,
                'params' => [
                    'namespace' => 'sales_archive_order_creditmemo_grid',
                    'filters' => ['order_increment_id' => '200000001'],
                ],
                'field_map' => ['created_at' => 'Created', 'order_created_at' => 'Order Date']
            ],
            'archive_shipment_grid_in_csv' => [
                'entity_code' => ArchivalList::SHIPMENT,
                'format' => ExportBase::CSV_FORMAT,
                'params' => [
                    'namespace' => 'sales_archive_order_shipment_grid',
                    'filters' => ['order_increment_id' => '200000001'],
                ],
                'field_map' => ['created_at' => 'Ship Date', 'order_created_at' => 'Order Date']
            ],
            'archive_shipment_grid_in_xml' => [
                'entity_code' => ArchivalList::SHIPMENT,
                'format' => ExportBase::XML_FORMAT,
                'params' => [
                    'namespace' => 'sales_archive_order_shipment_grid',
                    'filters' => ['order_increment_id' => '200000001'],
                ],
                'field_map' => ['created_at' => 'Ship Date', 'order_created_at' => 'Order Date']
            ],
        ];
    }

    /**
     * Returns archive entity by code and increment id.
     *
     * @param string $entityCode
     * @param string $incrementId
     * @return array
     */
    private function getArchiveEntityByIncrementId(string $entityCode, string $incrementId): array
    {
        return $this->getEntityData->execute($entityCode, 'increment_id', $incrementId);
    }
}
