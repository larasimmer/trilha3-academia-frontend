<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Observer;

use Exception;
use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Order Completed observer test.
 */
class OrderCompletedTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /** @var MutableScopeConfigInterface */
    private $config;

    /** @var array */
    private $defaultConfig = [];

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager = Bootstrap::getObjectManager();
        $this->config = $this->objectManager->get(MutableScopeConfigInterface::class);
    }

    /**
     * Test execute.
     *
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDataFixture Magento/Reward/_files/main_website_reward_exchange_rate.php
     * @magentoDataFixture Magento/Reward/_files/invoice_order_with_reward.php
     * @dataProvider orderCompletedDataProvider
     *
     * @param int $websiteId
     * @param int $customerId
     * @param bool $isCustomerGuest
     * @param float $baseGrandTotal
     * @param float $baseSubtotalCanceled
     * @param float $baseTaxCanceled
     * @param float $baseTotalPaid
     * @param int $pointsDelta
     * @param int $expectedPoints
     * @return void
     * @throws Exception
     */
    public function testExecute(
        int $websiteId,
        int $customerId,
        bool $isCustomerGuest,
        float $baseGrandTotal,
        float $baseSubtotalCanceled,
        float $baseTaxCanceled,
        float $baseTotalPaid,
        int $pointsDelta,
        int $expectedPoints
    ): void {
        $order = $this->getOrder(
            $websiteId,
            $customerId,
            $isCustomerGuest,
            $baseGrandTotal,
            $baseSubtotalCanceled,
            $baseTaxCanceled,
            $baseTotalPaid,
            $pointsDelta
        );
        $event = new Event(['order' => $order]);
        $eventObserver = new Observer(['event' => $event]);
        $this->prepareConfig('magento_reward/points/order', 1);
        $rewardObserver = $this->objectManager->create(OrderCompleted::class);
        $rewardObserver->execute($eventObserver);
        $this->assertEquals($expectedPoints, $order->getRewardPointsBalance());
    }

    /**
     * Returns order.
     *
     * @param int $websiteId
     * @param int $customerId
     * @param bool $isCustomerGuest
     * @param float $baseGrandTotal
     * @param float $baseSubtotalCanceled
     * @param float $baseTaxCanceled
     * @param float $baseTotalPaid
     * @param int $pointsDelta
     * @return Order
     */
    private function getOrder(
        int $websiteId,
        int $customerId,
        bool $isCustomerGuest,
        float $baseGrandTotal,
        float $baseSubtotalCanceled,
        float $baseTaxCanceled,
        float $baseTotalPaid,
        int $pointsDelta
    ): Order {
        /** @var Order $order */
        $order = $this->objectManager->create(Order::class)->loadByIncrementId('100000001');
        $order->setCustomerId($customerId);
        $order->setCustomerIsGuest($isCustomerGuest);
        $order->setBaseGrandTotal($baseGrandTotal);
        $order->setBaseSubtotalCanceled($baseSubtotalCanceled);
        $order->setBaseTaxCanceled($baseTaxCanceled);
        $order->setBaseTotalPaid($baseTotalPaid);
        $pointBalance = $pointsDelta + $baseTotalPaid;
        $order->setRewardPointsBalance($pointBalance);
        $order->setOrigData('base_grand_total', $baseGrandTotal);
        $order->setOrigData('base_subtotal_canceled', $baseSubtotalCanceled);
        $order->setOrigData('base_tax_canceled', $baseTaxCanceled);
        $order->setOrigData('base_total_paid', $baseTotalPaid);
        $store = $this->getStore($websiteId);
        $order->setStore($store);
        return $order;
    }

    /**
     * Prepare configuration
     *
     * Need this method because there is no availability to set website scope config values
     *
     * @param string $path
     * @param int $value
     * @return void
     */
    private function prepareConfig(string $path, int $value): void
    {
        $this->defaultConfig[$path] = $this->config->getValue(
            $path,
            ScopeInterface::SCOPE_WEBSITE,
            'base'
        );
        $this->config->setValue($path, $value, ScopeInterface::SCOPE_WEBSITE, 'base');
    }

    /**
     * Returns store
     *
     * @param int $websiteId
     * @return StoreInterface $store
     */
    private function getStore(int $websiteId): StoreInterface
    {
        $store = $this->objectManager->create(StoreInterface::class);
        $store->setWebsiteId($websiteId);
        return $store;
    }

    /**
     * order completed data provider.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array[]
     */
    public function orderCompletedDataProvider(): array
    {
        return [
            'Completed Order For Regular Customer With Tax' => [
                'website_id' => 1,
                'customer_id' => 1,
                'customer_is_guest' => false,
                'base_grand_total' => 100.00,
                'base_subtotal_canceled' => 30.00,
                'base_tax_canceled' => 10.00,
                'base_total_paid' => 60.00,
                'points_delta' => 100,
                'expected_points' => 160
            ],
            'Completed Order For Regular Customer Without Tax' => [
                'website_id' => 1,
                'customer_id' => 1,
                'customer_is_guest' => false,
                'base_grand_total' => 100.00,
                'base_subtotal_canceled' => 30.00,
                'base_tax_canceled' => 0.00,
                'base_total_paid' => 70.00,
                'points_delta' => 100,
                'expected_points' => 170
            ],
            'Completed Order For Guest Customer With Tax' => [
                'website_id' => 1,
                'customer_id' => 0,
                'customer_is_guest' => true,
                'base_grand_total' => 100.00,
                'base_subtotal_canceled' => 30.00,
                'base_tax_canceled' => 10.00,
                'base_total_paid' => 60.00,
                'points_delta' => 0,
                'expected_points' => 60
            ],
            'Completed Order For Guest Customer Without Tax' => [
                'website_id' => 1,
                'customer_id' => 0,
                'customer_is_guest' => true,
                'base_grand_total' => 100.00,
                'base_subtotal_canceled' => 30.00,
                'base_tax_canceled' => 0.00,
                'base_total_paid' => 70.00,
                'points_delta' => 0,
                'expected_points' => 70
            ]
        ];
    }
}
