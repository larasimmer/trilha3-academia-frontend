<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/CustomerSegment/_files/segment.php');
/** @var $segment \Magento\CustomerSegment\Model\Segment */
$segment = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
    \Magento\CustomerSegment\Model\Segment::class
);
$segment->load('Customer Segment 1', 'name');
$applyTo = '2';
$name = 'UpSell Rule';

/** @var $rule \Magento\TargetRule\Model\Rule */
$rule = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(Magento\TargetRule\Model\Rule::class);
$data = [
    'name' => $name,
    'is_active' => '1',
    'apply_to' => $applyTo,
    'use_customer_segment' => '1',
    'customer_segment_ids' => ['0' => $segment->getId()],
];
$rule->loadPost($data);
$rule->save();
