<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

$denyForGuestsRollbackFilePath = 'Magento/CatalogPermissions/_files/category_products_deny_for_guests_rollback.php';
Resolver::getInstance()->requireDataFixture($denyForGuestsRollbackFilePath);

$configurableProductRollbackFilePath = 'Magento/ConfigurableProduct/_files/product_configurable_rollback.php';
Resolver::getInstance()->requireDataFixture($configurableProductRollbackFilePath);
