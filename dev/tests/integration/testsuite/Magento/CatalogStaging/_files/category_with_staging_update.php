<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\VersionManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/Staging/_files/staging_temporary_update.php');

/** @var ObjectManagerInterface $objectManager */
$objectManager = Bootstrap::getObjectManager();
/** @var UpdateRepositoryInterface $updateRepository */
$updateRepository = $objectManager->create(UpdateRepositoryInterface::class);
/** @var VersionManager $versionManager */
$versionManager = $objectManager->get(VersionManager::class);
/** @var EntityManager $entityManager */
$entityManager = $objectManager->get(EntityManager::class);
/** @var CategoryFactory $categoryFactory */
$categoryFactory = $objectManager->get(CategoryFactory::class);
/** @var CategoryRepositoryInterface $categoryRepository */
$categoryRepository = $objectManager->get(CategoryRepositoryInterface::class);

// Creates category
$category = $categoryFactory->create();
$category->setName('Category with staging update')
    ->setParentId(2)
    ->setIsActive(true);
$categoryRepository->save($category);

// Creates a staging update for category
/** @var UpdateInterface $stagingUpdate */
$stagingUpdate = $updateRepository->get(2000);
$oldVersion = $versionManager->getCurrentVersion();
$versionManager->setCurrentVersionId($stagingUpdate->getId());
try {
    $entityManager->save($category, ['created_in' => $stagingUpdate->getId()]);
} finally {
    $versionManager->setCurrentVersionId($oldVersion->getId());
}
