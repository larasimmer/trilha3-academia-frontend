<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as CustomerGroupCollectionFactory;
use Magento\CustomerSegment\Model\ResourceModel\Segment\CollectionFactory as SegmentCollectionFactory;
use Magento\CustomerSegment\Model\Segment\Condition\Segment;
use Magento\Framework\Module\Manager;
use Magento\SalesRule\Api\Data\ConditionInterface;
use Magento\SalesRule\Api\Data\ConditionInterfaceFactory;
use Magento\SalesRule\Api\Data\RuleInterface;
use Magento\SalesRule\Api\Data\RuleInterfaceFactory;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\SalesRule\Model\Rule\Condition\Combine;
use Magento\Store\Model\WebsiteRepository;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

$objectManager = Bootstrap::getObjectManager();
/** @var Manager $moduleManager */
$moduleManager = $objectManager->get(Manager::class);

//This check is needed because Magento_SalesRule independent of Magento_CustomerSegment
if ($moduleManager->isEnabled('Magento_CustomerSegment')) {
    Resolver::getInstance()->requireDataFixture('Magento/CustomerSegment/_files/segment.php');

    /** @var RuleInterfaceFactory $salesRuleFactory */
    $salesRuleFactory = $objectManager->get(RuleInterfaceFactory::class);
    /** @var RuleRepositoryInterface $salesRuleRepository */
    $salesRuleRepository = $objectManager->get(RuleRepositoryInterface::class);
    /** @var ConditionInterfaceFactory $conditionFactory */
    $conditionFactory = $objectManager->get(ConditionInterfaceFactory::class);
    /** @var CustomerGroupCollectionFactory $customerGroupCollectionFactory */
    $customerGroupCollectionFactory = $objectManager->get(CustomerGroupCollectionFactory::class);
    $customerGroupIds = $customerGroupCollectionFactory->create()->getAllIds();
    /** @var WebsiteRepository $websiteRepository */
    $websiteRepository = $objectManager->get(WebsiteRepository::class);
    $websiteId = $websiteRepository->get('base')->getId();
    /** @var SegmentCollectionFactory $segmentCollectionFactory */
    $segmentCollectionFactory = $objectManager->get(SegmentCollectionFactory::class);
    $segment = $segmentCollectionFactory->create()->addFieldToFilter('name', 'Customer Segment 1')->getFirstItem();

    $conditionCustomerSegment = $conditionFactory->create();
    $conditionCustomerSegment->setConditionType(Segment::class);
    $conditionCustomerSegment->setValue($segment->getId());
    $conditionCustomerSegment->setOperator('==');

    $conditionCombine = $conditionFactory->create();
    $conditionCombine->setConditionType(Combine::class);
    $conditionCombine->setValue('1');
    $conditionCombine->setAggregatorType(ConditionInterface::AGGREGATOR_TYPE_ALL);
    $conditionCombine->setConditions([$conditionCustomerSegment]);

    $salesRule = $salesRuleFactory->create();
    $salesRule->setName('rule_25_off_with_customer_segment')
        ->setIsActive(1)
        ->setCouponType(RuleInterface::COUPON_TYPE_NO_COUPON)
        ->setSimpleAction(RuleInterface::DISCOUNT_ACTION_BY_PERCENT)
        ->setWebsiteIds([$websiteId])
        ->setCustomerGroupIds($customerGroupIds)
        ->setDiscountAmount(25)
        ->setCondition($conditionCombine);
    $salesRuleRepository->save($salesRule);
}
