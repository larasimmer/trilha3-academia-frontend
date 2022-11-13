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
use Magento\Store\Model\Website;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
$updateFactory = $objectManager->get(UpdateFactory::class);
$updateResourceModel = $objectManager->get(Update::class);
$updateRepository = $objectManager->get(UpdateRepository::class);
$versionManager = $objectManager->get(VersionManager::class);
/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);
$registry = Bootstrap::getObjectManager()->get(Registry::class);

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

$product = $productRepository->get('simplep1');
$productRepository->delete($product);

$update = $updateFactory->create();
$updateResourceModel->load($update, 'Product Update Second Store', 'name');
$versionManager->setCurrentVersionId($update->getId());
try {
    $productRepository->deleteById('simplep1');
} catch (NoSuchEntityException $e) {
    //Product already deleted
}
$updateRepository->delete($update);

/** @var Website $website */
$website = $objectManager->create(\Magento\Store\Model\Website::class);
$website->load('test_secondwebsite');

if ($website->getId()) {
    $website->delete();
}

/** @var Magento\Store\Model\Store $store */
$store = $objectManager->create(\Magento\Store\Model\Store::class);
$store->load('fixture_second_store');

if ($store->getId()) {
    $store->delete();
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
