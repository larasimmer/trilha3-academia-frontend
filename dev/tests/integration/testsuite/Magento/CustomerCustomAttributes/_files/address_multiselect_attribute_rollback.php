<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Customer\Model\Attribute;
use Magento\TestFramework\Helper\Bootstrap;

/** @var $attribute Attribute */
$attribute = Bootstrap::getObjectManager()->create(
    Attribute::class
);
$attribute->loadByCode('customer_address', 'multi_select_attribute_code');
$attribute->delete();
