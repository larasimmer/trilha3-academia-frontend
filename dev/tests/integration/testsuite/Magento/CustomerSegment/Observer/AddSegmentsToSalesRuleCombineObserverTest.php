<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerSegment\Observer;

use Magento\CustomerSegment\Model\Segment\Condition\Segment;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Test for add customer segments to sales rule combine observer.
 *
 * @see \Magento\CustomerSegment\Observer\AddSegmentsToSalesRuleCombineObserver
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class AddSegmentsToSalesRuleCombineObserverTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var ManagerInterface */
    private $eventManager;

    /** @var DataObjectFactory */
    private $dataObjectFactory;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->eventManager = $this->objectManager->get(ManagerInterface::class);
        $this->dataObjectFactory = $this->objectManager->get(DataObjectFactory::class);
    }

    /**
     * @return void
     */
    public function testSegmentsInSalesRuleConditions(): void
    {
        $additional = $this->dataObjectFactory->create();
        $this->eventManager->dispatch('salesrule_rule_condition_combine', ['additional' => $additional]);
        $this->assertTrue($additional->hasData('conditions'));
        $condition = current($additional->getData('conditions'));
        $this->assertEquals((string)__('Customer Segment'), $condition['label']);
        $this->assertEquals(Segment::class, $condition['value']);
    }

    /**
     * @magentoConfigFixture current_store customer/magento_customersegment/is_enabled 0
     *
     * @return void
     */
    public function testDisabledSegmentsInSalesRuleConditions(): void
    {
        $additional = $this->dataObjectFactory->create();
        $this->eventManager->dispatch('salesrule_rule_condition_combine', ['additional' => $additional]);
        $this->assertFalse($additional->hasData('conditions'));
    }
}
