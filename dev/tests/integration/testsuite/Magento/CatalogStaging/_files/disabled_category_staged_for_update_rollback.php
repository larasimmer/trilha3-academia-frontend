<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\ResourceModel\Update;
use Magento\Staging\Model\UpdateFactory;
use Magento\Staging\Model\VersionManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

$objectManager = Bootstrap::getObjectManager();
$updateFactory = $objectManager->get(UpdateFactory::class);
$updateRepository = $objectManager->get(UpdateRepositoryInterface::class);
$updateResourceModel = $objectManager->get(Update::class);
$versionManager = $objectManager->get(VersionManager::class);

$update = $updateFactory->create();
$updateResourceModel->load($update, 'Update for Category 8 Staging', 'name');
$versionManager->setCurrentVersionId($update->getId());

$categoryId = 8;
$updateRepository->delete($update);
try {
    Resolver::getInstance()->requireDataFixture('Magento/CatalogStaging/_files/disabled_categories_rollback.php');
} catch (NoSuchEntityException $e) {
    //category and the products in it already deleted
}

/** @var AdapterInterface $conn */
$conn = $updateResourceModel->getConnection();
$conn->delete($updateResourceModel->getTable('sequence_catalog_category'), sprintf('sequence_value = %s', $categoryId));
