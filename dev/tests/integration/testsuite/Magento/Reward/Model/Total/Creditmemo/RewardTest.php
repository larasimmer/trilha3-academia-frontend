<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Model\Total\Creditmemo;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Test credit memo total rewards
 */
class RewardTest extends TestCase
{
    /**
     * @var CreditmemoFactory
     */
    private $creditMemoFactory;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->creditMemoFactory = Bootstrap::getObjectManager()->create(CreditmemoFactory::class);
    }

    /**
     * @magentoDataFixture Magento/Reward/_files/main_website_reward_exchange_rate.php
     * @magentoDataFixture Magento/Reward/_files/invoice_order_with_reward.php
     * @magentoAppArea adminhtml
     */
    public function testCollect()
    {
        $objectManager = Bootstrap::getObjectManager();
        /** @var Order $order */
        $order = $objectManager->create(Order::class)->loadByIncrementId('100000001');
        $creditMemo = $this->creditMemoFactory->createByOrder($order, $order->getData());
        $this->assertEquals(1000, $creditMemo->getRewardPointsBalance());
    }
}
