<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/Banner/_files/banner.php');
Resolver::getInstance()->requireDataFixture('Magento/CatalogRule/_files/catalog_rule_10_off_not_logged.php');
$catalogRule = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
    \Magento\CatalogRule\Model\Rule::class
);
$ruleId = $catalogRule->getCollection()->getFirstItem()->getId();

$banner = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(\Magento\Banner\Model\Banner::class);
$banner->load('Test Dynamic Block', 'name')->setBannerCatalogRules([$ruleId])->save();
