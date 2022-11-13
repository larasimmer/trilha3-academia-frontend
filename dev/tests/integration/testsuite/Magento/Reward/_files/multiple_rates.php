<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Reward\Model\Reward\Rate;
use Magento\TestFramework\Helper\Bootstrap;

$dataToCurrency = [
    'website_id' => '1',
    'customer_group_id' => '1',
    'direction' => Rate::RATE_EXCHANGE_DIRECTION_TO_CURRENCY,
    'value' => 7,
    'equal_value' => 1,
];

$dataToPoints = [
    'website_id' => '1',
    'customer_group_id' => '1',
    'direction' => Rate::RATE_EXCHANGE_DIRECTION_TO_POINTS,
    'value' => 5,
    'equal_value' => 1,
];

/** @var Rate $rateToCurrency */
$rateToCurrency = Bootstrap::getObjectManager()->create(Rate::class);
$rateToCurrency->addData($dataToCurrency);
$rateToCurrency->save();

/** @var Rate $rateToPoints */
$rateToPoints = Bootstrap::getObjectManager()->create(Rate::class);
$rateToPoints->addData($dataToPoints);
$rateToPoints->save();
