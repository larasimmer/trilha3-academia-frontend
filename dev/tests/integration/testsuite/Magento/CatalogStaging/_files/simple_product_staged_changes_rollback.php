<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Registry;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Staging\Model\ResourceModel\Update;
use Magento\Staging\Model\UpdateFactory;
use Magento\Staging\Model\UpdateRepository;
use Magento\Staging\Model\VersionManager;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
$updateFactory = $objectManager->get(UpdateFactory::class);
$updateResourceModel = $objectManager->get(Update::class);
$updateRepository = $objectManager->get(UpdateRepository::class);
$versionManager = $objectManager->get(VersionManager::class);
$productRepository = $objectManager->get(ProductRepositoryInterface::class);
$registry = Bootstrap::getObjectManager()->get(Registry::class);

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

$product = $productRepository->get('simple');
$productRepository->delete($product);

$update = $updateFactory->create();
$updateResourceModel->load($update, 'Product Update Test', 'name');
$versionManager->setCurrentVersionId($update->getId());
try {
    $product = $productRepository->get('simple');
    $productRepository->delete($product);
} catch (NoSuchEntityException $e) {
    //Product already deleted
}
$updateRepository->delete($update);

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
