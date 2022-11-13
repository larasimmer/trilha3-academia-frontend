<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

/** @var \Magento\Framework\Registry $registry */
$registry = $objectManager->get(\Magento\Framework\Registry::class);

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);
$productRepository = $objectManager->create(\Magento\Catalog\Api\ProductRepositoryInterface::class);
Resolver::getInstance()->requireDataFixture('Magento/CatalogPermissions/_files/category_rollback.php');
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
