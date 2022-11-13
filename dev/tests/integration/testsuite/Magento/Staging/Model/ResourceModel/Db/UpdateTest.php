<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Model\ResourceModel\Db;

use Magento\Framework\ObjectManagerInterface;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\ResourceModel\Update;
use Magento\Staging\Model\UpdateFactory;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Tests for \Magento\Staging\Model\ResourceModel\Update
 *
 * @magentoAppArea adminhtml
 */
class UpdateTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Update
     */
    private $model;

    /**
     * @var UpdateRepositoryInterface
     */
    private $repository;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->model = $this->objectManager->create(Update::class);
        $this->repository = $this->objectManager->create(UpdateRepositoryInterface::class);
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Staging/_files/staging_temporary_update.php
     * @return void
     */
    public function testMovedFromTemporaryToPermanent(): void
    {
        $update = $this->repository->get(2000);
        $update->setStartTime(date('Y-m-d H:i:s', strtotime('+ 5 minutes', strtotime($update->getEndTime()))));
        $update->setEndTime('');
        $this->repository->save($update);

        // Recreate repository to resolve entity not from cache.
        $this->repository = $this->objectManager->create(UpdateRepositoryInterface::class);
        $oldUpdate = $this->repository->get($update->getOldId());

        $this->assertEquals($update->getId(), $oldUpdate->getMovedTo());
        $this->assertEmpty($oldUpdate->getRollbackId());
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Staging/_files/staging_update_moved.php
     *
     * @return void
     */
    public function testGetVersionMaxIdByTime()
    {
        $result = $this->model->getMaxIdByTime(time());
        $this->assertEquals(5500, $result);
    }
}
