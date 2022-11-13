<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

use Magento\Banner\Model\Banner;
use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

$objectManager = Bootstrap::getObjectManager();
$registry = $objectManager->get(Registry::class);

$banner = $objectManager->create(Banner::class);

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

try {
    $banner->load('Test Dynamic Block with location and segment', 'name');
    $banner->delete();
} catch (\Exception $ex) {
    //Nothing to remove
}

Resolver::getInstance()->requireDataFixture('Magento/CustomerSegment/_files/segment_customers_rollback.php');
Resolver::getInstance()->requireDataFixture('Magento/Customer/_files/customer_with_addresses_rollback.php');

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
