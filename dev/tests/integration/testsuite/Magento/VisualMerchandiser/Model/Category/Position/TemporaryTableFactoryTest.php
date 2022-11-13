<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VisualMerchandiser\Model\Category\Position;

use Magento\Catalog\Model\ResourceModel\Product;
use Monolog\Test\TestCase;

/**
 * Test for in-memory temporary table factory
 *
 * @magentoDbIsolation disabled
 */
class TemporaryTableFactoryTest extends TestCase
{
    /**
     * @var TemporaryTableFactory
     */
    private $model;

    /**
     * @var Product
     */
    private $resource;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $this->model = $objectManager->get(TemporaryTableFactory::class);
        $this->resource = $objectManager->get(Product::class);
    }

    /**
     * Test that data is saved in the temporary table and can be retrieved using SQL query
     */
    public function testCreate(): void
    {
        $data = [
            11 => 2,
            22 => 3,
            33 => 1,
        ];
        $tableName = $this->model->create($data);
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from($this->resource->getTable($tableName));
        $actual = $connection->fetchPairs($select);
        $this->assertEquals($data, $actual);
    }
}
