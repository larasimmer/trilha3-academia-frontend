<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Reward\Model\Reward\Rate;
use Magento\TestFramework\Helper\Bootstrap;

$data = [
    'website_id' => '0',
    'customer_group_id' => '0',
    'direction' => '1',
    'value' => 100,
    'equal_value' => 1,
];

/** @var Rate $rate */
$rate = Bootstrap::getObjectManager()->create(Rate::class);
$rate->addData($data);
$rate->save();

return $rate;
