<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/CatalogPermissions/_files/category_products_deny_rollback.php');

/**
 * Reindex
 */
$appDir = dirname(\Magento\TestFramework\Helper\Bootstrap::getInstance()->getAppTempDir());
$out = '';
// phpcs:ignore Magento2.Security.InsecureFunction
exec("php -f {$appDir}/bin/magento indexer:reindex catalogpermissions_category", $out);
