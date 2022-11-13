<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

$type = 'upsell';
$applyTo = $type == 'related' ? '1' : '2';

/** @var $rule \Magento\TargetRule\Model\Rule */
$rule = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(\Magento\TargetRule\Model\Rule::class);
$data = [
    'name' => $type,
    'is_active' => '1',
    'apply_to' => $applyTo,
    'use_customer_segment' => '0',
    'customer_segment_ids' => ['0' => ''],
];
$rule->loadPost($data);
$rule->save();
