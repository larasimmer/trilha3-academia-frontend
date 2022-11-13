<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\CustomerSegment\Model\Segment as CustomerSegment;
use Magento\SalesRule\Model\Rule;
use Magento\Store\Model\WebsiteRepository;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\SalesRule\Model\Rule\Condition\Combine;
use Magento\Customer\Model\ResourceModel\Group\Collection;
use Magento\CustomerSegment\Model\Segment\Condition\Segment;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/CustomerSegment/_files/segment_multiwebsite.php');

$objectManager = Bootstrap::getObjectManager();
/** @var $segment CustomerSegment */
$segment = $objectManager->create(CustomerSegment::class);
$segment->load('Customer Segment Multi-Website', 'name');
$mainWebsite = $objectManager->create(WebsiteRepository::class)->get('base');
$secondWebsite = $objectManager->create(WebsiteRepository::class)->get('secondwebsite');

/** @var Collection $groupCollection */
$groupCollection = $objectManager->get(Collection::class);

/** @var Rule $salesRule */
$salesRule = $objectManager->create(Rule::class);
$salesRule->setData(
    [
        'name' => '20% Off on orders with customer segment!',
        'is_active' => 1,
        'customer_group_ids' => $groupCollection->getAllIds(),
        'coupon_type' => Rule::COUPON_TYPE_NO_COUPON,
        'simple_action' => 'by_percent',
        'discount_amount' => 20,
        'discount_step' => 0,
        'stop_rules_processing' => 1,
        'website_ids' => [$mainWebsite->getId(), $secondWebsite->getId()],
    ]
);
$conditions = [
    'type' => Combine::class,
    'aggregator' => 'all',
    'value' => 1,
    'new_child' => '',
    'conditions' => [
        [
            'type' => Segment::class,
            'operator' => '==',
            'value' => $segment->getId(),
        ],
    ],
];

$salesRule->getConditions()->loadArray($conditions);

$salesRule->save();
