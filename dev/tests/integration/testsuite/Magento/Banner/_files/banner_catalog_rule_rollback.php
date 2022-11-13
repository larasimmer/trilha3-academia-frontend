<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/Banner/_files/banner_rollback.php');
Resolver::getInstance()->requireDataFixture('Magento/CatalogRule/_files/catalog_rule_10_off_not_logged_rollback.php');
