<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ScheduledImportExport\Model\Scheduled;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Framework\Exception\LocalizedException;

class OperationTest extends \Magento\TestFramework\Indexer\TestCase
{
    /**
     * @var \Magento\ScheduledImportExport\Model\Scheduled\Operation
     */
    protected $model;

    public static function setUpBeforeClass(): void
    {
        $db = Bootstrap::getInstance()->getBootstrap()
            ->getApplication()
            ->getDbInstance();
        if (!$db->isDbDumpExists()) {
            throw new \LogicException('DB dump does not exist.');
        }
        $db->restoreFromDbDump();

        parent::setUpBeforeClass();
    }

    /**
     * Set up before test
     */
    protected function setUp(): void
    {
        $this->model = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
            \Magento\ScheduledImportExport\Model\Scheduled\Operation::class
        );
    }

    /**
     * Get possible operation types
     *
     * @return array
     */
    public function getOperationTypesDataProvider()
    {
        return ['import' => ['$operationType' => 'import'], 'export' => ['$operationType' => 'export']];
    }

    /**
     * Test getInstance() method
     *
     * @dataProvider getOperationTypesDataProvider
     * @param $operationType
     */
    public function testGetInstance($operationType)
    {
        $this->model->setOperationType($operationType);
        $this->model->setFileInfo(['file_format' => 'csv'])
            ->setEntityAttributes(['export_filter' => []]);
        $string = new \Magento\Framework\Stdlib\StringUtils();
        $this->assertInstanceOf(
            'Magento\ScheduledImportExport\Model\\' . $string->upperCaseWords($operationType),
            $this->model->getInstance()
        );
    }

    /**
     * Test getHistoryFilePath() method in case when file info is not set
     *
     */
    public function testGetHistoryFilePathException()
    {
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);

        $this->model->getHistoryFilePath();
    }

    /**
     * @magentoDataFixture Magento/ScheduledImportExport/_files/operation.php
     * @magentoDbIsolation disabled
     */
    public function testSave()
    {
        /** @var \Magento\Framework\App\CacheInterface $cacheManager */
        $cacheManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
            \Magento\Framework\App\CacheInterface::class
        );
        $cacheManager->save('test_data', 'test_data_id', ['crontab']);
        $this->assertEquals('test_data', $cacheManager->load('test_data_id'));
        $this->model->load('export', 'operation_type');
        $this->model->setStartTime('06:00:00');
        $this->model->save();
        $result = $cacheManager->load('test_data_id');
        $this->assertEmpty($result);
    }

    /**
     * @magentoDataFixture Magento/ScheduledImportExport/_files/operation.php
     * @magentoDataFixture Magento/Catalog/_files/products_new.php
     * @magentoDbIsolation disabled
     */
    public function testRunAction()
    {
        $this->model->load('export', 'operation_type');

        $fileInfo = $this->model->getFileInfo();

        $targetDirectory = Bootstrap::getObjectManager()->get(Filesystem\Directory\TargetDirectory::class);
        $directory = $targetDirectory->getDirectoryWrite(DirectoryList::ROOT);
        $directory->create($fileInfo['file_path']);

        $this->model->run();

        $scheduledExport = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
            \Magento\ScheduledImportExport\Model\Export::class
        );
        $scheduledExport->setEntity($this->model->getEntityType());
        $scheduledExport->setOperationType($this->model->getOperationType());
        $scheduledExport->setRunDate($this->model->getLastRunDate());

        $filePath = $directory->getAbsolutePath($fileInfo['file_path']) . '/' .
            $scheduledExport->getScheduledFileName() . '.' . $fileInfo['file_format'];
        $this->assertTrue($directory->isExist($filePath));
    }

    /**
     * Test scheduled operation with not valid file format.
     *
     * @param array $data
     * @dataProvider beforeSaveDataProvider
     * @throws LocalizedException
     */
    public function testBeforeSaveWithException(array $data): void
    {
        $this->model->setData($data);
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Please correct the file format.');
        $this->model->save();
    }

    /**
     * @return array
     */
    public function beforeSaveDataProvider(): array
    {
        return [
            [
                [
                    'operation_type' => 'export',
                    'name' => 'Test Export ' . microtime(),
                    'entity_type' => 'catalog_product',
                    'file_info' => ['file_format' => 'not_valid', 'server_type' => 'file', 'file_path' => 'export'],
                ],
            ],
            [
                [
                    'operation_type' => 'export',
                    'name' => 'Test Export ' . microtime(),
                    'entity_type' => 'catalog_product',
                    'file_info' => '{"file_format": "not_valid", "server_type": "file", "file_path": "/"}',
                ],
            ],
        ];
    }

    /**
     * Test scheduled operation with not valid file format.
     *
     * @throws LocalizedException
     */
    public function testBeforeSaveWithExceptionJson(): void
    {
        $data = [
            'operation_type' => 'export',
            'name' => 'Test Export ' . microtime(),
            'entity_type' => 'catalog_product',
            'file_info' => '{"file_format":"not_valid","server_type":"file","file_path":"export"}',
        ];
        $this->model->setData($data);
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Please correct the file format.');
        $this->model->save();
    }
}
