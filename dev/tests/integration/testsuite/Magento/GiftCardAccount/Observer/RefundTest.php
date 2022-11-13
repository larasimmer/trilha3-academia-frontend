<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardAccount\Observer;

use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Sales\Api\CreditmemoManagementInterface;
use Magento\Sales\Model\Order\Creditmemo;
use PHPUnit\Framework\TestCase;

/**
 * Class for test refund observer
 */
class RefundTest extends TestCase
{
    // GiftCardAccount balance from fixture Magento/GiftCardAccount/_files/order_with_gift_card_account.php
    private static $giftCard1AmountInOrder = 15;

    const ORDER_INCREMENT_ID = '100000001';

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * Refund to exist GiftCardAccount.
     *
     * @return void
     * @magentoDataFixture Magento/GiftCardAccount/_files/complete_order_with_gift_card_account.php
     */
    public function testRefund(): void
    {
        $order = $this->getOrder();
        /** @var CreditmemoFactory $creditmemoFactory */
        $creditmemoFactory = $this->objectManager->get(CreditmemoFactory::class);
        /** @var  Creditmemo $creditmemo */
        $creditmemo = $creditmemoFactory->createByOrder($order, $order->getData());
        $refundedAmount = self::$giftCard1AmountInOrder;
        $creditmemo->setOrder($order);
        $creditmemo->setState(Creditmemo::STATE_REFUNDED);
        $creditmemo->setIncrementId(self::ORDER_INCREMENT_ID);
        $creditmemo->setBaseGiftCardsAmount($refundedAmount);
        $creditmemo->setGiftCardsAmount($refundedAmount);

        $creditmemoManagement = $this->objectManager->get(CreditmemoManagementInterface::class);
        $creditmemoManagement->refund($creditmemo);

        self::assertEquals(Order::STATE_COMPLETE, $order->getState());
    }

    /**
     * Get stored order
     *
     * @return Order
     */
    private function getOrder(): Order
    {
        /** @var Order $order */
        $order = $this->objectManager->get(Order::class);

        return $order->loadByIncrementId(self::ORDER_INCREMENT_ID);
    }
}
