<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Framework\Registry;
use Magento\Customer\Model\Attribute;

$objectManager = Bootstrap::getObjectManager();
$registry = $objectManager->get(Registry::class);
$currentArea = $registry->registry('isSecureArea');
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var $attribute Attribute */
$attribute = Bootstrap::getObjectManager()->create(
    Attribute::class
);
$attribute->loadByCode('customer_address', 'multi_select_code');
$attribute->delete();
$attribute->loadByCode('customer_address', 'elect_code');
$attribute->delete();
$attribute->loadByCode('customer_address', 'text_code');
$attribute->delete();

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', $currentArea);
