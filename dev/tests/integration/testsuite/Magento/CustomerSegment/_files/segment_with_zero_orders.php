<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\CustomerSegment\Model\Segment;
use Magento\CustomerSegment\Model\Segment\Condition\Combine\Root;
use Magento\CustomerSegment\Model\Segment\Condition\Sales\Ordersnumber;
use Magento\TestFramework\Helper\Bootstrap;

$data = [
    'name' => 'Customer Segment with zero orders',
    'website_ids' => [1],
    'is_active' => '1',
    'conditions' => [
        '1' => [
            'type' => Root::class,
            'aggregator' => 'all',
            'value' => '1',
            'new_child' => '',
        ],
        '1--1' => [
            'type' => Ordersnumber::class,
            'operator' => '==',
            'value' => 0,
            'aggregator' => 'all',
            'new_child' => '',
        ],
    ],
];
/** @var $segment Segment */
$segment = Bootstrap::getObjectManager()->create(Segment::class);
$segment->loadPost($data);
$segment->save();

$segment->matchCustomers();
