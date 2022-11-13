<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\CatalogRule\Model\Indexer\IndexBuilder;
use Magento\CatalogRule\Model\ResourceModel\Rule;
use Magento\CatalogRule\Model\RuleFactory;
use Magento\CatalogRuleStaging\Api\CatalogRuleStagingInterface;
use Magento\Customer\Model\GroupManagement;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\UpdateFactory;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
$updateFactory = $objectManager->get(UpdateFactory::class);
$updateRepository = $objectManager->get(UpdateRepositoryInterface::class);
$catalogRuleFactory = $objectManager->get(RuleFactory::class);
$catalogRuleResourceModel = $objectManager->get(Rule::class);
$catalogRuleStaging = $objectManager->get(CatalogRuleStagingInterface::class);

/** @var \Magento\CatalogRule\Model\Rule $catalogRule */
$catalogRule = $catalogRuleFactory->create();

$catalogRule
    ->setId(96)
    ->setIsActive(0)
    ->setName('Test Staged Catalog Rule')
    ->setCustomerGroupIds(GroupManagement::NOT_LOGGED_IN_ID)
    ->setDiscountAmount(10)
    ->setWebsiteIds([1])
    ->setSimpleAction('by_percent')
    ->setStopRulesProcessing(false)
    ->setSortOrder(0)
    ->setSubIsEnable(0)
    ->setSubDiscountAmount(0)
    ->save();

$startTime = date('Y-m-d H:i:s', strtotime('+1 day'));
$updateData = [
    'name' => 'Test Catalog Rule Update',
    'start_time' => $startTime,
    'is_campaign' => 0
];

$update = $updateFactory->create(['data' => $updateData]);
$updateRepository->save($update);

$catalogRule->setIsActive(1);
$catalogRuleStaging->schedule($catalogRule, $update->getId());

/** @var IndexBuilder $indexBuilder */
$indexBuilder = Bootstrap::getObjectManager()
    ->get(IndexBuilder::class);
$indexBuilder->reindexFull();
