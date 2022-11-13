<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\CatalogRule\Api\CatalogRuleRepositoryInterface;
use Magento\CatalogRule\Model\ResourceModel\Rule;
use Magento\CatalogRule\Model\RuleFactory;
use Magento\Staging\Model\UpdateFactory;
use Magento\Staging\Model\ResourceModel\Update;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
$ruleResourceModel = $objectManager->get(Rule::class);
$ruleRepository = $objectManager->get(CatalogRuleRepositoryInterface::class);
$ruleFactory = $objectManager->get(RuleFactory::class);
$updateFactory = $objectManager->get(UpdateFactory::class);
$updateResourceModel = $objectManager->get(Update::class);

$rule = $ruleFactory->create();
$ruleResourceModel->load($rule, 96);
$ruleRepository->deleteById($rule->getId());

$update = $updateFactory->create();
$updateResourceModel->load($update, 'Test Catalog Rule Update', 'name');
$updateResourceModel->delete($update);

$ruleId = 96;
/** @var AdapterInterface $conn */
$conn = $updateResourceModel->getConnection();
$conn->delete($updateResourceModel->getTable('sequence_catalogrule'), sprintf('sequence_value = %s', $ruleId));
