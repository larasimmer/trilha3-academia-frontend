<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\TestFramework\Helper\Bootstrap;
use Magento\TargetRule\Model\ResourceModel\Rule as TargetRuleResourceModel;
use Magento\TargetRule\Model\ResourceModel\Rule\Collection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

$objectManager = Bootstrap::getObjectManager();
/** @var TargetRuleResourceModel $targetRuleResourceModel */
$targetRuleResourceModel = $objectManager->get(TargetRuleResourceModel::class);
$targetRuleCollection = $objectManager->get(Collection::class);

/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->create(ProductRepositoryInterface::class);
try {
    $product = $productRepository->get('simple2');
    // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock
} catch (NoSuchEntityException $exception) {
}

$targetRuleCollection->addProductFilter($product->getId());
foreach ($targetRuleCollection->getItems() as $item) {
    try {
        $targetRuleResourceModel->delete($item);
        // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock
    } catch (NoSuchEntityException $exception) {
    }
}

Resolver::getInstance()->requireDataFixture('Magento/TargetRule/_files/products_with_attributes_rollback.php');
