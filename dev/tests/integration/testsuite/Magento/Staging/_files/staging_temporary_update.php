<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\TestFramework\Helper\Bootstrap;

/** @var Magento\Staging\Model\ResourceModel\Update $resourceModel */
$resourceModel = Bootstrap::getObjectManager()->create(\Magento\Staging\Model\ResourceModel\Update::class);
$entityIdField = $resourceModel->getIdFieldName();
$entityTable = $resourceModel->getMainTable();
$connection = $resourceModel->getConnection();

$updates = [
    [
        $entityIdField => 2000,
        'start_time' => date('Y-m-d H:i:s', strtotime('+1 day')),
        'rollback_id' => 2100,
        'is_rollback' => null,
        'name' => 'Temporary update 2000-2100',
    ],
    [
        $entityIdField => 2100,
        'start_time' => date('Y-m-d H:i:s', strtotime('+2 day')),
        'rollback_id' => null,
        'is_rollback' => 1,
        'name' => 'Rollback Temporary update 2000-2100',
    ],
];

$connection->insertMultiple($entityTable, $updates);
