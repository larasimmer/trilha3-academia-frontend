<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reminder\Model\ResourceModel;

use Magento\TestFramework\Helper\Bootstrap;

/**
 * RuleTest class.
 */
class RuleTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var Rule
     */
    private $ruleResource;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->ruleResource = $this->objectManager->create(Rule::class);
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDataFixture Magento/Customer/_files/quote.php
     * @magentoDataFixture Magento/Reminder/_files/rule.php
     * @magentoConfigFixture current_store catalog/frontend/flat_catalog_product 1
     */
    public function testGetCustomersForNotification()
    {
        $beforeYesterday = date('Y-m-d 03:00:00', strtotime('-2 day', time()));
        $customersForNotification = [['customer_id' => '1', 'coupon_id' => null, 'rule_id' => null, 'schedule' => '2',
                'log_sent_at_max' => $beforeYesterday, 'log_sent_at_min' => $beforeYesterday, ]];
        /** @var \Magento\Framework\App\ResourceConnection $resource */
        $resource = $this->objectManager->get(\Magento\Framework\App\ResourceConnection::class);
        $connection = $resource->getConnection();
        $connection->query("UPDATE {$resource->getTableName('quote')} SET updated_at = '{$beforeYesterday}'");

        $collection = $this->objectManager->create(
            \Magento\Reminder\Model\ResourceModel\Rule\Collection::class
        );
        $rules = $collection->addIsActiveFilter(1);
        foreach ($rules as $rule) {
            $customersForNotification[0]['rule_id'] = $rule->getId();
            $connection->query("INSERT INTO {$resource->getTableName('magento_reminder_rule_log')} " .
                "(`rule_id`, `customer_id`, `sent_at`) VALUES ({$rule->getId()}, 1, '{$beforeYesterday}');");
            $websiteIds = $rule->getWebsiteIds();
            foreach ($websiteIds as $websiteId) {
                $this->ruleResource->saveMatchedCustomers($rule, null, $websiteId, null);
            }
        }
        $this->assertEquals($customersForNotification, $this->ruleResource->getCustomersForNotification());
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/quote_with_customer.php
     * @magentoDataFixture Magento/Reminder/_files/reminder_rule_based_on_cart_rule.php
     */
    public function testSaveMatchedCustomers()
    {
        $rule = $this->objectManager->create(\Magento\Reminder\Model\Rule::class)
            ->load('reminder rule 1', 'name');
        $salesRule = $this->objectManager->create(\Magento\SalesRule\Model\Rule::class)
            ->load($rule->getSalesruleId());
        $this->ruleResource->saveMatchedCustomers($rule, $salesRule, 1);

        $salesRule = $this->objectManager->create(\Magento\SalesRule\Model\Rule::class)
            ->load($rule->getSalesruleId());
        $coupons = $salesRule->getCoupons();
        /** @var \Magento\SalesRule\Model\Coupon $coupon */
        $coupon = \array_shift($coupons);
        $this->assertNotEmpty($coupon);
        $this->assertNotEmpty($coupon->getCode());

        $quote = $this->objectManager->create(\Magento\Quote\Model\Quote::class)
            ->load('test01', 'reserved_order_id');
        $this->assertEquals(10, $quote->getGrandTotal());

        $quote->setCouponCode($coupon->getCode());
        $quote->collectTotals();
        $this->assertEquals($coupon->getCode(), $quote->getCouponCode());
        $this->assertEquals(9, $quote->getGrandTotal());
    }
}
