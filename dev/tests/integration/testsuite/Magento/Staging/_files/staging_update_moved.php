<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
use Magento\TestFramework\Helper\Bootstrap;

/**
 * @var $resourceModel \Magento\Staging\Model\ResourceModel\Update
 */
$resourceModel = Bootstrap::getObjectManager()->create(\Magento\Staging\Model\ResourceModel\Update::class);
$entityIdField = $resourceModel->getIdFieldName();
$entityTable = $resourceModel->getMainTable();

/**
 * @var $resource Magento\Framework\App\ResourceConnection
 */
$resource = Bootstrap::getObjectManager()->get(\Magento\Framework\App\ResourceConnection::class);
$connection = $resource->getConnection();

$updates = [
    [
        $entityIdField => 5500,
        'start_time' => date('Y-m-d H:i:s', strtotime('-5 minute')),
        'name' => 'Temporary update 5500-5510',
        'moved_to' => null
    ],
    [
        $entityIdField => 5510,
        'start_time' => date('Y-m-d H:i:s', strtotime('-1 minute')),
        'name' => 'Temporary update 5500-5510',
        'moved_to' => 5500
    ],
];

$connection->insertMultiple($entityTable, $updates);
