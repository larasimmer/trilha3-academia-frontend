<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Observer;

use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Creditmemo refund observer test.
 */
class CreditmemoRefundTest extends TestCase
{
    /**
     * @var ObjectManagerInterface|null
     */
    private $om = null;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->om = Bootstrap::getObjectManager();
    }

    /**
     * Test execute.
     *
     * @dataProvider creditmemoRefundDataProvider
     *
     * @param bool $rewardPointsBalanceRefundFlag
     * @param float $baseRewardCurrencyAmount
     * @param float $baseRwrdCrrncyAmntRefnded
     * @param float $baseRwrdCrrncyAmtInvoiced
     * @param bool $forcedCanCreditmemo
     * @return void
     */
    public function testExecute(
        bool $rewardPointsBalanceRefundFlag,
        float $baseRewardCurrencyAmount,
        float $baseRwrdCrrncyAmntRefnded,
        float $baseRwrdCrrncyAmtInvoiced,
        bool $forcedCanCreditmemo
    ): void {
        $creditmemo = $this->getCreditmemo(
            $rewardPointsBalanceRefundFlag,
            $baseRewardCurrencyAmount,
            $baseRwrdCrrncyAmntRefnded,
            $baseRwrdCrrncyAmtInvoiced
        );

        $event = new Event(['creditmemo' => $creditmemo]);
        $eventObserver = new Observer(['event' => $event]);

        $rewardObserver = $this->om->create(CreditmemoRefund::class);
        $rewardObserver->execute($eventObserver);

        $this->assertEquals($forcedCanCreditmemo, $creditmemo->getOrder()->getForcedCanCreditmemo());
    }

    /**
     * Creditmemo refund data provider.
     *
     * @return array[]
     */
    public function creditmemoRefundDataProvider(): array
    {
        return [
            'Reward points refund flag is not set' => [
                'reward_points_balance_refund_flag' => false,
                'base_reward_currency_amount' => 100,
                'base_rwrd_crrncy_amnt_refnded' => 100,
                'base_rwrd_crrncy_amt_invoiced' => 100,
                'forced_can_creditmemo' => false,
            ],
            'Reward points refund flag is set' => [
                'reward_points_balance_refund_flag' => true,
                'base_reward_currency_amount' => 100,
                'base_rwrd_crrncy_amnt_refnded' => 100,
                'base_rwrd_crrncy_amt_invoiced' => 100,
                'forced_can_creditmemo' => true,
            ],
        ];
    }

    /**
     * Returns creditmemo.
     *
     * @param bool $rewardPointsBalanceRefundFlag
     * @param float $baseRewardCurrencyAmount
     * @param float $baseRwrdCrrncyAmntRefnded
     * @param float $baseRwrdCrrncyAmtInvoiced
     * @return Creditmemo
     */
    private function getCreditmemo(
        bool $rewardPointsBalanceRefundFlag,
        float $baseRewardCurrencyAmount,
        float $baseRwrdCrrncyAmntRefnded,
        float $baseRwrdCrrncyAmtInvoiced
    ): Creditmemo {
        $order = $this->getOrder(
            $baseRwrdCrrncyAmntRefnded,
            $baseRwrdCrrncyAmtInvoiced
        );

        /** @var Creditmemo $creditmemo */
        $creditmemo = $this->om->get(Creditmemo::class);
        $creditmemo->setOrder($order);
        $creditmemo->setRewardPointsBalanceRefundFlag($rewardPointsBalanceRefundFlag);
        $creditmemo->setBaseRewardCurrencyAmount($baseRewardCurrencyAmount);

        return $creditmemo;
    }

    /**
     * Returns order.
     *
     * @param float $baseRwrdCrrncyAmntRefnded
     * @param float $baseRwrdCrrncyAmtInvoiced
     * @return Order
     */
    private function getOrder(
        float $baseRwrdCrrncyAmntRefnded,
        float $baseRwrdCrrncyAmtInvoiced
    ): Order {
        /** @var Order $order */
        $order = $this->om->get(Order::class);
        $order->setForcedCanCreditmemo(true);
        $order->setBaseRwrdCrrncyAmntRefnded($baseRwrdCrrncyAmntRefnded);
        $order->setBaseRwrdCrrncyAmtInvoiced($baseRwrdCrrncyAmtInvoiced);

        return $order;
    }
}
