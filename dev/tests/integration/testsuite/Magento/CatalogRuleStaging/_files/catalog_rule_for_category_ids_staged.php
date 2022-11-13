<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\CatalogRule\Api\CatalogRuleRepositoryInterface;
use Magento\CatalogRule\Model\Indexer\IndexBuilder;
use Magento\CatalogRule\Model\Rule\Condition\Combine;
use Magento\CatalogRuleStaging\Api\CatalogRuleStagingInterface;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\CatalogRule\Model\Rule\Condition\Product;
use Magento\CatalogRule\Model\RuleFactory;
use Magento\Staging\Model\UpdateFactory;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

/** @var \Magento\CatalogRule\Model\Rule $catalogRule */
$catalogRule = $objectManager->get(RuleFactory::class)->create();
$catalogRule->loadPost([
    'name' => 'CatalogRule for Category 8',
    'is_active' => '0',
    'stop_rules_processing' => 0,
    'website_ids' => [1],
    'customer_group_ids' => [0, 1],
    'discount_amount' => 50,
    'simple_action' => 'by_percent',
    'from_date' => '',
    'to_date' => '',
    'sort_order' => 0,
    'sub_is_enable' => 0,
    'sub_discount_amount' => 0,
    'conditions' => [
        '1' => [
            'type' => Combine::class,
            'aggregator' => 'all',
            'value' => '1',
            'new_child' => '',
        ],
        '1--1' => [
            'type' => Product::class,
            'attribute' => 'category_ids',
            'operator' => '==',
            'value' => '8',
        ],
    ],
]);

/** @var CatalogRuleRepositoryInterface $catalogRuleRepository */
$catalogRuleRepository = $objectManager->get(CatalogRuleRepositoryInterface::class);
$catalogRuleRepository->save($catalogRule);

// stage changes to catalog rule by activating it
$startTime = date('Y-m-d H:i:s', strtotime('+1 day'));
$updateData = [
    'name' => 'Test CatalogRule Update for Cat 8',
    'start_time' => $startTime,
    'is_campaign' => 0,
    'is_rollback' => null,
];
$updateFactory = $objectManager->get(UpdateFactory::class);
$update = $updateFactory->create(['data' => $updateData]);
$updateRepository = $objectManager->get(UpdateRepositoryInterface::class);
$updateRepository->save($update);

$catalogRule->setIsActive(1);
$catalogRuleStaging = $objectManager->get(CatalogRuleStagingInterface::class);
$catalogRuleStaging->schedule($catalogRule, $update->getId());

/** @var IndexBuilder $indexBuilder */
$indexBuilder = Bootstrap::getObjectManager()
    ->get(IndexBuilder::class);
$indexBuilder->reindexFull();
