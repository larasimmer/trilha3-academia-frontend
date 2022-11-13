<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\TestFramework\Helper\Bootstrap;
use Magento\Framework\Registry;
use Magento\Customer\Model\Attribute;
use Magento\Framework\Exception\LocalizedException;

$objectManager = Bootstrap::getObjectManager();

/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var Attribute $attribute */
$attribute = $objectManager->create(Attribute::class);

try {
    $attribute->loadByCode('customer_address', 'document');
    $attribute->delete();
} catch (LocalizedException $e) {
    // If was not loaded
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
