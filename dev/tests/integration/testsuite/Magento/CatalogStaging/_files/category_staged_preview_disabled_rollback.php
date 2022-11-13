<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Staging\Model\VersionManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\UpdateFactory;
use Magento\Staging\Model\ResourceModel\Update;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

$objectManager = Bootstrap::getObjectManager();
$updateFactory = $objectManager->get(UpdateFactory::class);
$updateRepository = $objectManager->get(UpdateRepositoryInterface::class);
$updateResourceModel = $objectManager->get(Update::class);
$versionManager = $objectManager->get(VersionManager::class);

$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

$categoryId = 333;
Resolver::getInstance()->requireDataFixture('Magento/Catalog/_files/category_rollback.php');

$update = $updateFactory->create();
$updateResourceModel->load($update, 'Preview Category Staging', 'name');
$versionManager->setCurrentVersionId($update->getId());
$updateRepository->delete($update);
try {
    Resolver::getInstance()->requireDataFixture('Magento/Catalog/_files/category_rollback.php');
} catch (NoSuchEntityException $e) {
    //category already deleted
}

$update = $updateFactory->create();
$updateResourceModel->load($update, 'Preview Disabled Category Staging', 'name');
$versionManager->setCurrentVersionId($update->getId());
$updateRepository->delete($update);
try {
    Resolver::getInstance()->requireDataFixture('Magento/Catalog/_files/category_rollback.php');
} catch (NoSuchEntityException $e) {
    //category already deleted
}

/** @var AdapterInterface $conn */
$conn = $updateResourceModel->getConnection();
$conn->delete($updateResourceModel->getTable('sequence_catalog_category'), sprintf('sequence_value = %s', $categoryId));

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
