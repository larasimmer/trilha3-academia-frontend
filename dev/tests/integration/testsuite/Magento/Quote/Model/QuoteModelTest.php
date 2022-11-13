<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Quote\Model;

use Magento\Customer\Model\CustomerFactory;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\WebsiteRepository;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\ObjectManagerInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\TestFramework\Quote\Model\GetQuoteByReservedOrderId;
use Magento\TestFramework\SalesRule\Model\GetSalesRuleByName;
use Magento\CustomerSegment\Model\Customer as CustomerSegment;
use Magento\Customer\Model\ResourceModel\CustomerRepository;

/**
 * Test for \Magento\Quote\Model\Quote.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class QuoteModelTest extends \PHPUnit\Framework\TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var CustomerRepository */
    private $customerRepository;

    /** @var WebsiteRepository */
    private $websiteRepository;

    /** @var ProductRepository */
    private $productRepository;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();

        $this->customerRepository = $this->objectManager->get(CustomerRepository::class);
        $this->productRepository = $this->objectManager->get(ProductRepository::class);
        $this->websiteRepository = $this->objectManager->get(WebsiteRepository::class);
    }

    /**
     * Check that discount applies to non customer store when Account Share set as Global.
     *
     * @magentoDbIsolation disabled
     * @magentoDataFixture Magento/Catalog/_files/product_virtual.php
     * @magentoDataFixture Magento/SalesRule/_files/cart_rule_20_percent_customer_segment.php
     * @magentoDataFixture Magento/Quote/_files/quote_for_customer_and_custom_store.php
     * @magentoConfigFixture current_store customer/account_share/scope 0
     * @return void
     */
    public function testInitTotals(): void
    {
        $customerEmail = 'customer@example.com';
        $mainWebsite = $this->websiteRepository->get('base');
        $secondWebsite = $this->websiteRepository->get('secondwebsite');

        $product = $this->productRepository->get('virtual-product');
        $product->setWebsiteIds([$mainWebsite->getId(), $secondWebsite->getId()]);
        $product = $this->productRepository->save($product);

        $customer = $this->customerRepository->get($customerEmail);
        $this->reinitCustomerSegment($customer, $secondWebsite);

        $quote = $this->objectManager->get(GetQuoteByReservedOrderId::class)->execute('tsg-123456789');
        $quote->addProduct($product, 2);
        $result = $quote->collectTotals();

        $salesRule = $this->objectManager->get(GetSalesRuleByName::class)
            ->execute('20% Off on orders with customer segment!');
        $this->assertEquals($salesRule->getRuleId(), $result->getAppliedRuleIds());
        $this->assertEquals(20, $result->getSubtotal());
        $this->assertEquals(16, $result->getSubtotalWithDiscount());
    }

    /**
     * Reinitialize all segments for specific customer on specific website.
     *
     * @param CustomerInterface $customer
     * @param WebsiteInterface $website
     * @return void
     */
    private function reinitCustomerSegment(CustomerInterface $customer, WebsiteInterface $website): void
    {
        $customerModel = $this->objectManager->get(CustomerFactory::class)->create()->setId($customer->getId());
        $this->objectManager->get(CustomerSegment::class)->processCustomer($customerModel, $website);
    }
}
