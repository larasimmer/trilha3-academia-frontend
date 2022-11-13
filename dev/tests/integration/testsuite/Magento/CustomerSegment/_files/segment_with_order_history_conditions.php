<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\CustomerSegment\Model\SegmentFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\CustomerSegment\Model\Segment\Condition\Combine\Root;
use Magento\CustomerSegment\Model\Segment\Condition\Customer\Attributes;

$objectManager = Bootstrap::getObjectManager();
$segmentFactory = $objectManager->get(SegmentFactory::class);
$segment = $segmentFactory->create();
/**
 * Get POST data emulating segment with following condition:
 * – If Product was ordered and matches ALL of these Conditions:
 * -- Period equals or less than 365 Days Up To Date
 * -- Product Category is 2
 */
$segmentPostData =  [
    'name' => 'Segment with order history condition',
    'description' => '',
    'website_ids' => ['1'],
    'is_active' => '1',
    'conditions' => [
        '1' => [
            'type' => 'Magento\\CustomerSegment\\Model\\Segment\\Condition\\Combine\\Root',
            'aggregator' => 'all',
            'value' => '1',
            'new_child' => '',
        ],
        '1--1' => [
            'type' => 'Magento\\CustomerSegment\\Model\\Segment\\Condition\\Product\\Combine\\History',
            'operator' => '==',
            'value' => 'ordered_history',
            'aggregator' => 'all',
            'new_child' => '',
        ],
        '1--1--1' => [
            'type' => 'Magento\\CustomerSegment\\Model\\Segment\\Condition\\Uptodate',
            'operator' => '>=',
            'value' => '365',
        ],
        '1--1--2' => [
            'type' => 'Magento\\CustomerSegment\\Model\\Segment\\Condition\\Product\\Attributes',
            'attribute' => 'category_ids',
            'operator' => '==',
            'value' => '2',
        ],
    ],
];
$segment->loadPost($segmentPostData);
$segment->save();
