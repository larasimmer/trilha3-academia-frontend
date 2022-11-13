<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ScheduledImportExport\Cron;

use LogicException;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\Write;
use Magento\ScheduledImportExport\Model\Scheduled\Operation;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Indexer\TestCase;

class ScheduledLogCleanTest extends TestCase
{
    /**
     * @inheritdoc
     */
    public static function setUpBeforeClass(): void
    {
        $db = Bootstrap::getInstance()->getBootstrap()
            ->getApplication()
            ->getDbInstance();

        if (!$db->isDbDumpExists()) {
            throw new LogicException('DB dump does not exist.');
        }

        $db->restoreFromDbDump();

        parent::setUpBeforeClass();
    }

    /**
     * @codingStandardsIgnoreStart
     * @magentoConfigFixture current_store crontab/default/jobs/magento_scheduled_import_export_log_clean/schedule/cron_expr 1
     * @codingStandardsIgnoreEnd
     * @magentoDataFixture Magento/ScheduledImportExport/_files/operation.php
     * @magentoDataFixture Magento/Catalog/_files/products_new.php
     * @magentoDbIsolation disabled
     *
     * @return void
     */
    public function testScheduledLogClean(): void
    {
        // Set up
        /** @var Operation $operation */
        $operation = Bootstrap::getObjectManager()->create(
            Operation::class
        );

        $operation->load('export', 'operation_type');

        $fileInfo = $operation->getFileInfo();
        $historyPath = $operation->getHistoryFilePath();

        // Create export directory if not exist
        $filesystem = Bootstrap::getObjectManager()->get(Filesystem::class);
        /** @var Write $directory */
        $directory = $filesystem->getDirectoryWrite(DirectoryList::VAR_IMPORT_EXPORT);
        $directory->create($fileInfo['file_path']);

        $operation->run();

        $this->assertTrue($directory->isExist($historyPath));

        $observer = Bootstrap::getObjectManager()
            ->get(ScheduledLogClean::class);
        $observer->execute(true);

        // Verify
        $this->assertFalse($directory->isExist($historyPath));
    }
}
