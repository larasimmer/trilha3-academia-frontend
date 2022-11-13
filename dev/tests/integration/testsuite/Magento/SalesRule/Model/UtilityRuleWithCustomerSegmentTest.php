<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesRule\Model;

use Magento\Customer\Model\CustomerFactory;
use Magento\CustomerSegment\Model\Customer as CustomerSegment;
use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory as RuleCollectionFactory;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Test for utility sales rule model.
 *
 * @magentoDbIsolation enabled
 * @magentoDataFixture Magento/Checkout/_files/quote_with_address_saved.php
 * @magentoDataFixture Magento/SalesRule/_files/cart_rule_25_percent_customer_segment.php
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UtilityRuleWithCustomerSegmentTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var Manager */
    private $moduleManager;

    /** @var Utility */
    private $utility;

    /** @var CartRepositoryInterface */
    private $cartRepository;

    /** @var RuleCollectionFactory */
    private $ruleCollectionFactory;

    /** @var CustomerSegment */
    private $customerSegment;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var CustomerFactory */
    private $customerFactory;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->moduleManager = $this->objectManager->get(Manager::class);
        //This check is needed because Magento_SalesRule independent of Magento_CustomerSegment
        if (!$this->moduleManager->isEnabled('Magento_CustomerSegment')) {
            $this->markTestSkipped('Magento_CustomerSegment module disabled.');
        }
        $this->utility = $this->objectManager->get(Utility::class);
        $this->cartRepository = $this->objectManager->get(CartRepositoryInterface::class);
        $this->ruleCollectionFactory = $this->objectManager->get(RuleCollectionFactory::class);
        $this->customerSegment = $this->objectManager->get(CustomerSegment::class);
        $this->storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $this->customerFactory = $this->objectManager->get(CustomerFactory::class);
    }

    /**
     * @magentoConfigFixture current_store customer/magento_customersegment/is_enabled 1
     *
     * @return void
     */
    public function testProcessRuleWithCustomerSegmentInCondition(): void
    {
        $customerId = 1;
        $baseWebsite = $this->storeManager->getWebsite('base');
        $quote = $this->cartRepository->getForCustomer($customerId);
        $this->reinitCustomerSegment($customerId, $baseWebsite);
        $salesRule = $this->getSalesRuleByName('rule_25_off_with_customer_segment');
        $result = $this->utility->canProcessRule($salesRule, $quote->getShippingAddress());
        $this->assertTrue($result);
        $this->assertTrue($salesRule->getIsValidForAddress($quote->getShippingAddress()->getId()));
    }

    /**
     * @magentoConfigFixture current_store customer/magento_customersegment/is_enabled 0
     *
     * @return void
     */
    public function testProcessRuleWithDisabledCustomerSegmentInCondition(): void
    {
        $customerId = 1;
        $baseWebsite = $this->storeManager->getWebsite('base');
        $quote = $this->cartRepository->getForCustomer($customerId);
        $this->reinitCustomerSegment($customerId, $baseWebsite);
        $salesRule = $this->getSalesRuleByName('rule_25_off_with_customer_segment');
        $result = $this->utility->canProcessRule($salesRule, $quote->getShippingAddress());
        $this->assertFalse($result);
        $this->assertFalse($salesRule->getIsValidForAddress($quote->getShippingAddress()->getId()));
    }

    /**
     * Get sales rule by name.
     *
     * @param string $ruleName
     * @return Rule
     */
    private function getSalesRuleByName(string $ruleName): Rule
    {
        $salesRule = $this->ruleCollectionFactory->create()->addFieldToFilter('name', $ruleName)->getFirstItem();
        $this->assertNotNull($salesRule->getId());

        return $salesRule;
    }

    /**
     * Reinitialize all segments for specific customer on specific website.
     *
     * @param int $customerId
     * @param WebsiteInterface $website
     * @return void
     */
    private function reinitCustomerSegment(int $customerId, WebsiteInterface $website): void
    {
        $customerModel = $this->customerFactory->create()->setId($customerId);
        $this->customerSegment->processCustomer($customerModel, $website);
    }
}
