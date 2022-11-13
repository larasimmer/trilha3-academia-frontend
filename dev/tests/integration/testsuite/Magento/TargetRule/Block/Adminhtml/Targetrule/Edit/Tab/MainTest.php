<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Block\Adminhtml\Targetrule\Edit\Tab;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\LayoutInterface;
use Magento\TargetRule\Model\Rule;
use Magento\TargetRule\Model\RuleFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Helper\Xpath;
use PHPUnit\Framework\TestCase;

/**
 * Test for target rule main edit tab.
 *
 * @see \Magento\TargetRule\Block\Adminhtml\Targetrule\Edit\Tab\Main
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class MainTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var Registry */
    private $registry;

    /** @var RuleFactory */
    private $ruleFactory;

    /** @var Main */
    private $block;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->registry = $this->objectManager->get(Registry::class);
        $this->ruleFactory = $this->objectManager->get(RuleFactory::class);
        $this->block = $this->objectManager->get(LayoutInterface::class)->createBlock(Main::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        $this->registry->unregister('current_target_rule');

        parent::tearDown();
    }

    /**
     * @magentoAppIsolation enabled
     *
     * @return void
     */
    public function testPrepareForm(): void
    {
        $targetRule = $this->ruleFactory->create();
        $this->registerTargetRule($targetRule);
        $prepareFormMethod = new \ReflectionMethod(Main::class, '_prepareForm');
        $prepareFormMethod->setAccessible(true);
        $prepareFormMethod->invoke($this->block);
        $form = $this->block->getForm();
        foreach (['from_date', 'to_date'] as $id) {
            $element = $form->getElement($id);
            $this->assertNotNull($element);
            $this->assertNotEmpty($element->getDateFormat());
        }
    }

    /**
     * @magentoConfigFixture current_store customer/magento_customersegment/is_enabled 1
     * @magentoDataFixture Magento/CustomerSegment/_files/segment.php
     *
     * @return void
     */
    public function testCustomerSegmentsFields(): void
    {
        $targetRule = $this->ruleFactory->create();
        $this->registerTargetRule($targetRule);
        $html = $this->block->toHtml();
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath("//select[@id='rule_use_customer_segment']", $html),
            'Customer Segment field wasn\'t found.'
        );
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath("//select[@id='rule_customer_segment_ids']", $html),
            'Customer Segment ids field wasn\'t found.'
        );
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(
                "//select[@id='rule_customer_segment_ids']/option[contains(text(), 'Customer Segment 1')]",
                $html
            ),
            'Option in field customer segment ids wasn\'t found.'
        );
    }

    /**
     * @magentoConfigFixture current_store customer/magento_customersegment/is_enabled 0
     *
     * @return void
     */
    public function testDisabledCustomerSegmentsFields(): void
    {
        $targetRule = $this->ruleFactory->create();
        $this->registerTargetRule($targetRule);
        $html = $this->block->toHtml();
        $this->assertEquals(
            0,
            Xpath::getElementsCountForXpath("//select[@id='rule_use_customer_segment']", $html),
            'Customer Segment field was found with disabled config.'
        );
        $this->assertEquals(
            0,
            Xpath::getElementsCountForXpath("//select[@id='rule_customer_segment_ids']", $html),
            'Customer Segment ids field was found with disabled config.'
        );
    }

    /**
     * Register target rule in registry.
     *
     * @param Rule $targetRule
     * @return void
     */
    private function registerTargetRule(Rule $targetRule): void
    {
        $this->registry->unregister('current_target_rule');
        $this->registry->register('current_target_rule', $targetRule);
    }
}
