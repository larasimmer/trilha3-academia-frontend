<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerBalance\Model;

use Magento\Sales\Model\Order;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class CreditMemoResolverTest extends TestCase
{
    /**
     * @var Order
     */
    private $order;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        /** @var ObjectManager $objectManager */
        $objectManager = Bootstrap::getObjectManager();
        /** @var $payment Payment */
        $payment = $objectManager->create(
            Payment::class
        );
        $payment->setMethod('checkmo');
        $this->order = $objectManager->create(
            Order::class,
            [
                'data' => [
                    'state' => Order::STATE_PROCESSING,
                    'total_paid' => 20,
                    'base_total_paid' => 20,
                    'total_refunded' => 0,
                    OrderInterface::PAYMENT => $payment,
                ]
            ]
        );
    }

    /**
     * Checks if Credit Memo can be created depends on different totals.
     *
     * @param float|null $totalInvoiced
     * @param float|null $balanceInvoiced
     * @param float|null $rewardInvoiced
     * @param float|null $totalRefunded
     * @param float|null $balanceRefunded
     * @param float|null $rewardRefunded
     * @param bool $expected
     * @dataProvider totalsDataProvider
     */
    public function testIsCreditMemoAvailable(
        ?float $totalInvoiced,
        ?float $balanceInvoiced,
        ?float $rewardInvoiced,
        ?float $totalRefunded,
        ?float $balanceRefunded,
        ?float $rewardRefunded,
        bool $expected
    ) {
        $this->order->setBaseTotalInvoiced($totalInvoiced);
        $this->order->setBaseCustomerBalanceInvoiced($balanceInvoiced);
        $this->order->setBaseRwrdCrrncyAmtInvoiced($rewardInvoiced);

        $this->order->setBaseTotalRefunded($totalRefunded);
        $this->order->setBaseCustomerBalanceRefunded($balanceRefunded);
        $this->order->setBaseRwrdCrrncyAmntRefnded($rewardRefunded);

        self::assertEquals($expected, $this->order->canCreditmemo());
    }

    /**
     * Gets list of totals variations.
     *
     * @return array
     */
    public function totalsDataProvider(): array
    {
        return [
            [
                'totalInvoiced' => 10,
                'balanceInvoiced' => 0,
                'rewardInvoiced' => 0,
                'totalRefunded' => 0,
                'balanceRefunded' => 0,
                'rewardRefunded' => 0,
                'expected' => true
            ],
            [
                'totalInvoiced' => 20,
                'balanceInvoiced' => 0,
                'rewardInvoiced' => 0,
                'totalRefunded' => 10,
                'balanceRefunded' => 3,
                'rewardRefunded' => 6,
                'expected' => true
            ],
            [
                'totalInvoiced' => 15,
                'balanceInvoiced' => 5,
                'rewardInvoiced' => 0,
                'totalRefunded' => 15,
                'balanceRefunded' => 5,
                'rewardRefunded' => 0,
                'expected' => false
            ],
            [
                'totalInvoiced' => 0,
                'balanceInvoiced' => 5,
                'rewardInvoiced' => 15,
                'totalRefunded' => 0,
                'balanceRefunded' => 5,
                'rewardRefunded' => 15,
                'expected' => false
            ],
            [
                'totalInvoiced' => 0,
                'balanceInvoiced' => null,
                'rewardInvoiced' => null,
                'totalRefunded' => 0,
                'balanceRefunded' => null,
                'rewardRefunded' => null,
                'expected' => true
            ],
            [
                'totalInvoiced' => 20,
                'balanceInvoiced' => 0,
                'rewardInvoiced' => 0,
                'totalRefunded' => 10,
                'balanceRefunded' => 10,
                'rewardRefunded' => 0,
                'expected' => true
            ],
        ];
    }
}
