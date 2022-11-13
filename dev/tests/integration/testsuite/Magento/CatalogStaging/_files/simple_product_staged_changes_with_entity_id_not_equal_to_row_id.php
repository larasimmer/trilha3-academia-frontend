<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

$objectManager = Bootstrap::getObjectManager();
$productRepository = $objectManager->get(ProductRepositoryInterface::class);
Resolver::getInstance()->requireDataFixture('Magento/CatalogStaging/_files/simple_product_staged_changes_2.php');
$product = $productRepository->get('asimpleproduct');
if ($product->getEntityId() === $product->getRowId()) {
    Resolver::getInstance()
        ->requireDataFixture('Magento/CatalogStaging/_files/simple_product_staged_changes_2_rollback.php');
    Resolver::getInstance()->requireDataFixture('Magento/CatalogStaging/_files/simple_product_staged_changes_2.php');
}
