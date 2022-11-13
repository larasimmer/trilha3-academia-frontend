<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\CatalogRule\Model\ResourceModel\Rule;
use Magento\Framework\App\ResourceConnection;
use Magento\Staging\Model\ResourceModel\Update;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Creates a catalog rule with an update
 *
 * @param string $name
 * @param int $updateId
 * @throws Exception
 */
function createCatalogRuleWithUpdate(string $name, int $updateId)
{
    //create catalog rule
    $objectManager = Bootstrap::getObjectManager();
    /** @var \Magento\CatalogRule\Model\Rule $catalogRule */
    $catalogRule = $objectManager->create(\Magento\CatalogRule\Model\Rule::class);
    $catalogRule
        ->setIsActive(1)
        ->setName($name)
        ->setDiscountAmount(10)
        ->setWebsiteIds([0 => 1])
        ->setSimpleAction('by_percent')
        ->setStopRulesProcessing(false)
        ->setSortOrder(0)
        ->setSubIsEnable(0)
        ->setSubDiscountAmount(0)
        ->save();

    //create update
    /** @var ResourceConnection $resource */
    $resource = $objectManager->get(ResourceConnection::class);
    $connection = $resource->getConnection();
    /** @var Update $resourceModelUpdate */
    $resourceModelUpdate = $objectManager->create(Update::class);
    $entityTable = $resourceModelUpdate->getTable('staging_update');
    $updateDatetime = new DateTime('+1 week');
    $update = [
        'id' => $updateId,
        'start_time' => $updateDatetime->format('Y-m-d H:i:s'),
        'name' => 'Update name',
        'is_campaign' => 0
    ];
    $connection->query(
        "INSERT INTO {$entityTable} (`id`,  `start_time`, `name`, `is_campaign`)"
        . " VALUES (:id, :start_time, :name, :is_campaign);",
        $update
    );

    //update existing Sales Rule entity
    $catalogRule->setCreatedIn($updateId)->save();
}
