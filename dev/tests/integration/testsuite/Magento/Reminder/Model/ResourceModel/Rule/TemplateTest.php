<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reminder\Model\ResourceModel\Rule;

/**
 * Class TemplateTest
 */
class TemplateTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var \Magento\Store\Api\Data\StoreInterface
     */
    private $store;

    /**
     * @var \Magento\Email\Model\Template
     */
    private $template;

    /**
     * @var \Magento\Reminder\Model\Rule
     */
    private $ruleCreate;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

        /** @var $store \Magento\Store\Model\Store */
        $this->store = $this->objectManager->get(\Magento\Store\Model\StoreManagerInterface::class)
            ->getStore();

        /** @var \Magento\Email\Model\Template $template */
        $this->template = $this->objectManager->create(\Magento\Email\Model\Template::class);
        $this->template->setTemplateCode(
            'fixture_tpl'
        )->setTemplateText(
            '<p>Reminder email</p>This is a reminder email'
        )->setTemplateType(
            2
        )->setTemplateSubject(
            'Subject'
        )->setTemplateSenderName(
            'CustomerSupport'
        )->setTemplateSenderEmail(
            'support@example.com'
        )->setTemplateActual(
            1
        )->save();

        $this->ruleCreate = $this->objectManager->create(\Magento\Reminder\Model\Rule::class);
        $this->ruleCreate->setData(
            [
                'name' => 'My Rule',
                'description' => 'My Rule Desc',
                'conditions_serialized' => json_encode([]),
                'condition_sql' => 1,
                'is_active' => 1,
                'salesrule_id' => null,
                'schedule' => null,
                'default_label' => null,
                'default_description' => null,
                'from_date' => null,
                'to_date' => null,
                'store_templates' => [$this->store->getId() => $this->template->getId()],
            ]
        )->save();
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        $this->template->delete();
        $this->ruleCreate->delete();
    }

    /**
     * Test creation of reminder rule with custom template.
     */
    public function testTemplate()
    {
        $dateModel = $this->objectManager->create(\Magento\Framework\Stdlib\DateTime\DateTime::class);
        $collection = $this->objectManager->create(\Magento\Reminder\Model\ResourceModel\Rule\Collection::class);
        $collection->addDateFilter($dateModel->date());
        $this->assertEquals(1, $collection->count());

        /** @var \Magento\Reminder\Model\Rule $rule */
        $rule = $collection->getFirstItem();
        $this->assertInstanceOf(\Magento\Reminder\Model\Rule::class, $rule);
        $this->assertEquals('My Rule', $rule->getName());

        $storeData = $rule->getStoreData($rule->getId(), $this->store->getId());
        $this->assertNotNull($storeData);
        $this->assertEquals($this->template->getId(), $storeData['template_id']);
    }
}
