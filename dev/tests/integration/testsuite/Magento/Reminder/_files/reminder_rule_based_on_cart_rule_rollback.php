<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

$collection = $objectManager->get(\Magento\SalesRule\Model\ResourceModel\Rule\Collection::class);
foreach ($collection as $rule) {
    $rule->delete();
}

$collection = $objectManager->get(\Magento\Reminder\Model\ResourceModel\Rule\Collection::class);
foreach ($collection as $rule) {
    $rule->delete();
}
