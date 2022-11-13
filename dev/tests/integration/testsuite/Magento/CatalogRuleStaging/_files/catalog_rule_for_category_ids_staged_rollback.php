<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\CatalogRule\Model\ResourceModel\Rule;
use Magento\Staging\Model\UpdateFactory;
use Magento\Staging\Model\ResourceModel\Update;
use Magento\Staging\Model\VersionManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Framework\App\ResourceConnection;

$objectManager = Bootstrap::getObjectManager();
$updateFactory = $objectManager->get(UpdateFactory::class);
$updateResourceModel = $objectManager->get(Update::class);
$versionManager = $objectManager->get(VersionManager::class);

//delete catalog rules
/** @var ResourceConnection $resource */
$resource = $objectManager->get(ResourceConnection::class);
$connection = $resource->getConnection();
/** @var Rule $ruleResource */
$ruleResource = $objectManager->create(Rule::class);
$connection->delete($ruleResource->getMainTable());

$update = $updateFactory->create();
$updateResourceModel->load($update, 'Test CatalogRule Update for Cat 8', 'name');
$versionManager->setCurrentVersionId($update->getId());
$updateResourceModel->delete($update);

/** @var \Magento\CatalogRule\Model\Indexer\IndexBuilder $indexBuilder */
$indexBuilder = $objectManager->get(\Magento\CatalogRule\Model\Indexer\IndexBuilder::class);
$indexBuilder->reindexFull();
