<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

$websiteRepository = $objectManager->create(\Magento\Store\Api\WebsiteRepositoryInterface::class);
$websites = $websiteRepository->getList();
$websiteIds = [];
foreach ($websites as $website) {
    $websiteIds[] = (int) $website->getId();
}

$productRepository = $objectManager->create(\Magento\Catalog\Api\ProductRepositoryInterface::class);
$stockRegistry = $objectManager->get(\Magento\CatalogInventory\Api\StockRegistryInterface::class);
$stockItemRepository = $objectManager->get(\Magento\CatalogInventory\Api\StockItemRepositoryInterface::class);
$stockRegistryStorage = $objectManager->get(\Magento\CatalogInventory\Model\StockRegistryStorage::class);
for ($i = 1; $i <= 2; $i++) {
    $product = $objectManager->create(\Magento\Catalog\Api\Data\ProductInterface::class);
    $product->setTypeId('simple')
        ->setAttributeSetId(4)
        ->setName('Simple Product ' . $i)
        ->setSku('simple' . $i)
        ->setPrice(10 * $i)
        ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
        ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
        ->setWebsiteIds($websiteIds);
    $product = $productRepository->save($product);

    $stockItem = $stockRegistry->getStockItem($product->getId());
    $stockItem->setUseConfigManageStock(true);
    $stockItem->setQty(100 * $i);
    $stockItem->setIsInStock(true);
    $stockItemRepository->save($stockItem);
    $stockRegistryStorage->removeStockItem($product->getId());
    $stockRegistryStorage->removeStockStatus($product->getId());
}
