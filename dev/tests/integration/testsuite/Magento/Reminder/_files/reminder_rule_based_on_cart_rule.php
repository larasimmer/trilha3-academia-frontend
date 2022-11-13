<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

$salesRule = $objectManager->create(\Magento\SalesRule\Model\Rule::class)
    ->setName('cart rule 1')
    ->setIsActive(1)
    ->setIsAdvanced(1)
    ->setCouponType(\Magento\SalesRule\Model\Rule::COUPON_TYPE_AUTO)
    ->setWebsiteIds('1')
    ->setCustomerGroupIds('1')
    ->setSimpleAction(\Magento\SalesRule\Model\Rule::BY_PERCENT_ACTION)
    ->setDiscountAmount(10);
$salesRule->save();

$conditions = [
    'type' => \Magento\Reminder\Model\Rule\Condition\Combine\Root::class,
    'value' => '1',
    'aggregator' => 'all',
    'conditions' => [
        [
            'type' => \Magento\Reminder\Model\Rule\Condition\Cart::class,
            'aggregator' => 'all',
            'operator' => '==',
            'conditions' => [
                [

                    'type' => \Magento\Reminder\Model\Rule\Condition\Cart\Amount::class,
                    'attribute' => 'subtotal',
                    'operator' => '>',
                    'value' => '9',
                ],
            ],
        ],
    ],
];
$reminderRule = $objectManager->create(\Magento\Reminder\Model\Rule::class)
    ->setName('reminder rule 1')
    ->setIsActive(1)
    ->setSalesruleId($salesRule->getId())
    ->setConditionsSerialized(json_encode($conditions))
    ->setWebsiteIds('1');
$reminderRule->save();
