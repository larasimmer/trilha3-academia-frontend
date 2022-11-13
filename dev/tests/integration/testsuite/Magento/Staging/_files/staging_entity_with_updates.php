<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

use Magento\TestFramework\Helper\Bootstrap;
use Magento\Framework\App\ResourceConnection;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Catalog\Model\ResourceModel\Product;
use Magento\Framework\DB\Adapter\AdapterInterface;

$objectManager = Bootstrap::getObjectManager();
/** @var Product $resourceModel */
$resourceModel = $objectManager->create(Product::class);
$entityIdField = $resourceModel->getIdFieldName();
$entityTable = $resourceModel->getTable('catalog_product_entity');
$sequenceTable = $resourceModel->getTable('sequence_product');
/** @var AdapterInterface $connection */
$connection = $resourceModel->getConnection();

$endTime = strtotime('+50 minutes');
$updates = [
    [
        'name' => 'Update 1',
        'start_time' => date('Y-m-d H:i:s', strtotime('+40 minutes')),
        'end_time' => date('Y-m-d H:i:s', $endTime),
        'rollback_id' => $endTime,
    ],
    [
        'name' => 'Update 1',
        'start_time' => date('Y-m-d H:i:s', strtotime('+5 minutes')),
        'end_time' => date('Y-m-d H:i:s', $endTime),
        'rollback_id' => $endTime,
    ],
];

$rollBack = [
    'name' => 'Rollback for "Update 1"',
    'start_time' => date('Y-m-d H:i:s', $endTime),
    'is_rollback' => 1,
];

/** @var UpdateRepositoryInterface $updateRepository */
$updateRepository = $objectManager->get(UpdateRepositoryInterface::class);
/** @var UpdateInterface $entity */
$entity = $objectManager->create(UpdateInterface::class, ['data' => $rollBack]);
$updateRepository->save($entity);

$connection->insert($sequenceTable, ['sequence_value' => 1]);
$rowIdNum = 1;
$previousCreatedIn = 1;
foreach ($updates as $update) {
    /** @var UpdateInterface $entity */
    $entity = $objectManager->create(UpdateInterface::class, ['data' => $update]);
    $updateRepository->save($entity);

    $entityUpdate = [
        'row_id' => $rowIdNum++,
        $entityIdField => 1,
        'created_in' => $previousCreatedIn,
        'updated_in' => $entity->getId(),
        'attribute_set_id' => 1,
        'type_id' => 'simple',
        'sku' => 'productSku',
        'has_options' => 0,
        'required_options' => 0,
    ];
    $connection->insert($entityTable, $entityUpdate);
    $previousCreatedIn = $entity->getId();
}
