<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\TestFramework\Helper\Bootstrap;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\TestFramework\SalesRule\Model\GetSalesRuleByName;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

$objectManager = Bootstrap::getObjectManager();

$salesRule = $objectManager->get(GetSalesRuleByName::class)->execute('20% Off on orders with customer segment!');
if ($salesRule !== null) {
    /** @var RuleRepositoryInterface $ruleRepository */
    $ruleRepository = $objectManager->get(RuleRepositoryInterface::class);
    $ruleRepository->deleteById($salesRule->getRuleId());
}
Resolver::getInstance()->requireDataFixture('Magento/CustomerSegment/_files/segment_multiwebsite_rollback.php');
