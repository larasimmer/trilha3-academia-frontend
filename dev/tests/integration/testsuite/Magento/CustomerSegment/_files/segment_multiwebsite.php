<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Customer\Model\CustomerRegistry;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/Store/_files/core_second_third_fixturestore.php');
Resolver::getInstance()->requireDataFixture('Magento/Customer/_files/customer.php');

$objectManager = Bootstrap::getObjectManager();
/** @var \Magento\Store\Model\Website $mainWebsite */
/** @var \Magento\Store\Model\Website $secondWebsite */
$mainWebsite = $objectManager->create(\Magento\Store\Model\Website::class)->load('base');
$secondWebsite = $objectManager->create(\Magento\Store\Model\Website::class)->load('secondwebsite');

if (!isset($customer)) {
    /** @var CustomerRegistry $customerRegistry */
    $customerRegistry = Bootstrap::getObjectManager()->create(CustomerRegistry::class);
    $customer = $customerRegistry->retrieve(1);
}

/** @var $segmentFactory \Magento\CustomerSegment\Model\SegmentFactory */
$segmentFactory = $objectManager->create(\Magento\CustomerSegment\Model\SegmentFactory::class);

$data = [
    'name'          => 'Customer Segment Multi-Website',
    'website_ids'   => [$mainWebsite->getId(), $secondWebsite->getId()],
    'is_active'     => '1',
    'apply_to'      => \Magento\CustomerSegment\Model\Segment::APPLY_TO_VISITORS_AND_REGISTERED,
];

/** @var $segment \Magento\CustomerSegment\Model\Segment */
$segment = $segmentFactory->create();
$segment->loadPost($data);
$segment->save();

$conditions = [
    1 => [
        'type' => \Magento\CustomerSegment\Model\Segment\Condition\Combine\Root::class,
        'aggregator' => 'any',
        'value' => '1',
        'new_child' => '',
    ],
    '1--1' => [
        'type' => \Magento\CustomerSegment\Model\Segment\Condition\Customer\Attributes::class,
        'attribute' => 'email',
        'operator' => '==',
        'value' => $customer->getEmail(),
    ]
];
$data['segment_id'] = $segment->getSegmentId();
$data['conditions'] = $conditions;

$segment->loadPost($data);
$segment->save();
$segment->matchCustomers();
