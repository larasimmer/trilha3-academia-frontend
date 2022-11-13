<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Framework\Module\Manager;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\SalesRule\Model\GetSalesRuleByName;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

$objectManager = Bootstrap::getObjectManager();
/** @var Manager $moduleManager */
$moduleManager = $objectManager->get(Manager::class);

//This check is needed because Magento_SalesRule independent of Magento_CustomerSegment
if ($moduleManager->isEnabled('Magento_CustomerSegment')) {
    Resolver::getInstance()->requireDataFixture('Magento/CustomerSegment/_files/segment_rollback.php');

    /** @var RuleRepositoryInterface $ruleRepository */
    $ruleRepository = $objectManager->get(RuleRepositoryInterface::class);
    /** @var GetSalesRuleByName $getSalesRuleByName */
    $getSalesRuleByName = $objectManager->get(GetSalesRuleByName::class);
    $salesRule = $getSalesRuleByName->execute('rule_25_off_with_customer_segment');
    if ($salesRule !== null) {
        $ruleRepository->deleteById($salesRule->getRuleId());
    }
}
