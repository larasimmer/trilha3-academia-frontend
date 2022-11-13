<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Model\Action;

use Magento\Framework\DataObject;
use Magento\Framework\ObjectManagerInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Reward\Helper\Data as RewardDataHelper;
use Magento\Reward\Model\Action\OrderExtra as OrderExtraAction;
use Magento\Reward\Model\Reward;
use Magento\Reward\Model\Reward\History as RewardHistory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Reward points acquire by purchase test.
 */
class OrderExtraTest extends TestCase
{
    /**
     * @var ObjectManagerInterface|null
     */
    private $om = null;

    /**
     * @var OrderExtraAction|null
     */
    private $orderExtraAction = null;

    /**
     * @inheridoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->om = Bootstrap::getObjectManager();
        $this->orderExtraAction = new OrderExtraAction($this->getRewardDataHelper());
        $this->orderExtraAction->setReward($this->getReward());
    }

    /**
     * Reward points acquiring by purchase is off.
     *
     * @magentoConfigFixture admin_website magento_reward/points/order 0
     * @magentoConfigFixture current_store tax/calculation/price_includes_tax 1
     *
     * @return void
     */
    public function testGetPointsUnavailable(): void
    {
        $this->assertEquals(
            0,
            $this->orderExtraAction->getPoints(0),
            'Reward points are unavailable if acquiring by purchase is off.'
        );
    }

    /**
     * Reward points by existing quote, prices included tax.
     *
     * @magentoConfigFixture admin_website magento_reward/points/order 1
     * @magentoConfigFixture current_store tax/calculation/price_includes_tax 1
     *
     * @magentoDataFixture Magento/Reward/_files/rate_to_points.php
     *
     * @return void
     */
    public function testGetPointsQuotePriceIncludeTax(): void
    {
        $this->orderExtraAction->setQuote($this->getQuote());

        $this->assertEquals(
            8500,
            $this->orderExtraAction->getPoints(0),
            'Incorrect reward points value.'
        );
    }

    /**
     * Reward points by existing quote, prices not included tax.
     *
     * @magentoConfigFixture admin_website magento_reward/points/order 1
     * @magentoConfigFixture current_store tax/calculation/price_includes_tax 0
     *
     * @magentoDataFixture Magento/Reward/_files/rate_to_points.php
     *
     * @return void
     */
    public function testGetPointsQuotePriceNotIncludeTax(): void
    {
        $this->orderExtraAction->setQuote($this->getQuote());

        $this->assertEquals(
            8000,
            $this->orderExtraAction->getPoints(0),
            'Incorrect reward points value.'
        );
    }

    /**
     * Reward points by non-existing quote, prices included tax.
     *
     * @magentoConfigFixture admin_website magento_reward/points/order 1
     * @magentoConfigFixture current_store tax/calculation/price_includes_tax 1
     *
     * @magentoDataFixture Magento/Reward/_files/rate_to_points.php
     *
     * @return void
     */
    public function testGetPointsNoQuotePriceIncludeTax(): void
    {
        $this->orderExtraAction->setHistory($this->getHistory());
        $this->orderExtraAction->setEntity($this->getEntity());

        $this->assertEquals(
            8500,
            $this->orderExtraAction->getPoints(0),
            'Incorrect reward points value.'
        );
    }

    /**
     * Reward points by non-existing quote, prices not included tax.
     *
     * @magentoConfigFixture admin_website magento_reward/points/order 1
     * @magentoConfigFixture current_store tax/calculation/price_includes_tax 0
     *
     * @magentoDataFixture Magento/Reward/_files/rate_to_points.php
     *
     * @return void
     */
    public function testGetPointsNoQuotePriceNotIncludeTax(): void
    {
        $this->orderExtraAction->setHistory($this->getHistory());
        $this->orderExtraAction->setEntity($this->getEntity());

        $this->assertEquals(
            8000,
            $this->orderExtraAction->getPoints(0),
            'Incorrect reward points value.'
        );
    }

    /**
     * Gets RewardDataHelper.
     *
     * @return RewardDataHelper
     */
    private function getRewardDataHelper(): RewardDataHelper
    {
        return $this->om->get(RewardDataHelper::class);
    }

    /**
     * Gets Reward.
     *
     * @return Reward
     */
    private function getReward(): Reward
    {
        /** @var Reward $reward */
        $reward = $this->om->get(Reward::class);
        $reward->setWebsiteId(0);
        $reward->setCustomerGroupId(0);

        return $reward;
    }

    /**
     * Gets Quote.
     *
     * @return Quote
     */
    private function getQuote(): Quote
    {
        /** @var Address $address */
        $address = $this->om->get(Address::class);
        $address->setBaseShippingInclTax(15);
        $address->setShippingInclTax(30);
        $address->setBaseShippingAmount(10);
        $address->setShippingAmount(20);
        $address->setBaseTaxAmount(10);
        $address->setTaxAmount(20);

        /** @var Quote $quote */
        $quote = $this->om->get(Quote::class);
        $quote->setIsVirtual(1);
        $quote->setBillingAddress($address);
        $quote->setShippingAddress($address);
        $quote->setBaseGrandTotal(100);
        $quote->setGrandTotal(200);

        return $quote;
    }

    /**
     * Gets Entity.
     *
     * @return DataObject
     */
    private function getEntity(): DataObject
    {
        $do = $this->om->create(DataObject::class);
        $do->setIncrementId(1);
        $do->setBaseTotalPaid(100);
        $do->setTotalPaid(200);
        $do->setBaseShippingInclTax(15);
        $do->setShippingInclTax(30);
        $do->setBaseShippingAmount(10);
        $do->setShippingAmount(20);
        $do->setBaseTaxAmount(10);
        $do->setTaxAmount(20);

        return $do;
    }

    /**
     * Gets RewardHistory.
     *
     * @return RewardHistory
     */
    private function getHistory(): RewardHistory
    {
        return $this->om->get(RewardHistory::class);
    }
}
