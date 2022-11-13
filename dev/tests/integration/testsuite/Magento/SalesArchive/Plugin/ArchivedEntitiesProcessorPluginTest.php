<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesArchive\Plugin;

use Magento\SalesArchive\Model\ResourceModel\Archive;
use Magento\SalesArchive\Model\ResourceModel\Order\Collection as ArchiveOrderCollection;
use Magento\TestFramework\Helper\Bootstrap;

class ArchivedEntitiesProcessorPluginTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Archive
     */
    private $archive;

    /**
     * @var Grid
     */
    private $orderGrid;

    /**
     * @var ArchiveOrderCollection
     */
    private $archiveOrderCollection;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->archive = $objectManager->get(Archive::class);
        $this->orderGrid = $objectManager->get('Magento\Sales\Model\ResourceModel\Order\Grid');
        $this->archiveOrderCollection = $objectManager->get(ArchiveOrderCollection::class);
    }

    /**
     * Test archived sales orders entities are present in records
     *
     * @magentoDbIsolation disabled
     * @magentoConfigFixture default_store dev/grid/async_indexing 1
     * @magentoDataFixture Magento/SalesArchive/_files/orders_with_customer.php
     */
    public function testArchivedEntities(): void
    {
        $incrementIds = ['100000007', '100000009', '100000011'];
        $this->archive->moveToArchive('order', 'increment_id', $incrementIds);
        $this->archive->removeFromGrid('order', 'increment_id', $incrementIds);
        $this->orderGrid->refreshBySchedule();
        $archiveOrderItems = $this->archiveOrderCollection->getItems();

        $this->assertEquals(count($incrementIds), count($archiveOrderItems));
    }

    protected function tearDown(): void
    {
        $incrementIds  = ['100000007', '100000008', '100000009', '100000010', '100000011', '100000012'];
        $this->archive->removeFromArchive('order', 'increment_id', $incrementIds);
    }
}
