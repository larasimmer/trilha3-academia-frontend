<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

//delete existing updates
use Magento\CatalogRule\Model\ResourceModel\Rule;
use Magento\Framework\App\ResourceConnection;
use Magento\Staging\Model\ResourceModel\Update;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

/** @var ResourceConnection $resource */
$resource = $objectManager->get(ResourceConnection::class);
$connection = $resource->getConnection();

/** @var Update $resourceModel */
$resourceModel = $objectManager->create(Update::class);
$connection->delete($resourceModel->getMainTable());

//delete existing catalog rules
/** @var Rule $ruleResource */
$ruleResource = $objectManager->create(Rule::class);
$connection->delete($ruleResource->getMainTable());
