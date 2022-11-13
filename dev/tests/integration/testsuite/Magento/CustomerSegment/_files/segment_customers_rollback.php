<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\TestFramework\Workaround\Override\Fixture\Resolver;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Framework\Registry;
use Magento\CustomerSegment\Model\Segment;

Resolver::getInstance()->requireDataFixture('Magento/Customer/_files/three_customers_rollback.php');

$objectManager = Bootstrap::getObjectManager();
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

$segment = $objectManager->get(Segment::class);
for ($i = 0; $i < 3; $i++) {
    $ruleName = sprintf('Customer Segment %1$d', $i);
    $segment->load($ruleName, 'name');
    if ($segment->getId()) {
        $segment->delete();
    }
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
