<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardAccount\Observer;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\ObjectManagerInterface;
use Magento\GiftCardAccount\Api\Data\GiftCardAccountInterface;
use Magento\GiftCardAccount\Api\GiftCardAccountRepositoryInterface;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Block\Adminhtml\Creditmemo;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CreditmemoSaveAfterTest extends \PHPUnit\Framework\TestCase
{
    // GiftCardAccount balance from fixture Magento/GiftCardAccount/_files/order_with_gift_card_account.php
    private static $giftCard1AmountInOrder = 10;

    // GiftCardAccount balance from fixture Magento/GiftCardAccount/_files/order_with_gift_card_account.php
    private static $giftCard2AmountInOrder = 15;

    // Order increment Id from fixture Magento/GiftCardAccount/_files/order_with_gift_card_account.php
    private static $orderIncrementId = '100000001';

    /**
     * @var CreditmemoSaveAfter
     */
    private $creditmemoSaveAfter;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->creditmemoSaveAfter = $this->objectManager->create(CreditmemoSaveAfter::class);
    }

    /**
     * Refund to exist GiftCardAccount.
     * Refund amount sum up with balance from db.
     *
     * @magentoDataFixture Magento/GiftCardAccount/_files/creditmemo_with_gift_card_account.php
     */
    public function testRefundToGiftCardAccountWithExistingAccounts()
    {
        $fBalance = $this->getGiftCardAccount('TESTCODE1')
            ->getBalance();
        $sBalance = $this->getGiftCardAccount('TESTCODE2')
            ->getBalance();
        $observer = $this->getObserver();
        /** @var Creditmemo $creditMemo */
        $creditMemo = $observer->getEvent()
            ->getCreditmemo();
        $creditMemo->setBaseGiftCardsAmount(20);

        $this->creditmemoSaveAfter->execute($observer);

        $giftCardAccount = $this->getGiftCardAccount('TESTCODE1');
        self::assertEquals($fBalance + 10, $giftCardAccount->getBalance());

        // second Gift Card should refunded partially
        $giftCardAccount = $this->getGiftCardAccount('TESTCODE2');
        self::assertEquals($sBalance + 10, $giftCardAccount->getBalance());
    }

    /**
     * Refund if the GiftCardAccounts were deleted.
     * In this case are creating new accounts with the same code and balance from the order.
     *
     * @magentoDataFixture  Magento/GiftCardAccount/_files/creditmemo_with_deleted_gift_card_account.php
     */
    public function testRefundToGiftCardAccountWithDeletedAccounts()
    {
        $observer = $this->getObserver();
        $this->creditmemoSaveAfter->execute($observer);

        $giftcardAccount = $this->getGiftCardAccount('TESTCODE1');
        self::assertEquals($giftcardAccount->getBalance(), self::$giftCard1AmountInOrder);

        $giftcardAccount = $this->getGiftCardAccount('TESTCODE2');
        self::assertNull($giftcardAccount);
    }

    /**
     * Tests messages added to the order after returns amount to gift card account.
     *
     * @magentoDataFixture  Magento/GiftCardAccount/_files/creditmemo_with_deleted_gift_card_account.php
     */
    public function testComments()
    {
        $observer = $this->getObserver();
        /** @var Creditmemo $creditmemo */
        $creditmemo = $observer->getEvent()
            ->getCreditmemo();
        $refundedAmount = self::$giftCard1AmountInOrder + self::$giftCard2AmountInOrder;
        $creditmemo->setBaseGiftCardsAmount($refundedAmount);

        $this->creditmemoSaveAfter->execute($observer);

        $order = $this->getOrder();
        $comments = $order->getAllStatusHistory();
        $realHistoryComments = [];
        foreach ($comments as $comment) {
            $realHistoryComments[] = $comment->getComment();
        }

        self::assertContains('We refunded $10.00 to Gift Card (TESTCODE1)', $realHistoryComments);
        self::assertContains('We refunded $15.00 to Gift Card (TESTCODE2)', $realHistoryComments);
    }

    /**
     * Tests messages added to the order after returns amount to Store Credit.
     *
     * @param float $refundedAmount
     * @param float $customerBalanceRefunded
     * @param int $expectedMessages
     * @param array $expectedAmount
     * @magentoDataFixture Magento/GiftCardAccount/_files/creditmemo_with_gift_card_account.php
     * @magentoConfigFixture customer/magento_customerbalance/is_enabled 1
     * @dataProvider giftCardDataProvider
     */
    public function testRefundToStoreCreditComments(
        float $refundedAmount,
        float $customerBalanceRefunded,
        int $expectedMessages,
        array $expectedAmount
    ) {
        /** @var Observer $observer*/
        $observer = $this->getObserver();
        /** @var Creditmemo $creditmemo */
        $creditmemo = $observer->getEvent()
            ->getCreditmemo();
        $creditmemo->setCustomerBalanceRefundFlag(true)
            ->setBaseGiftCardsAmount($refundedAmount)
            ->setBsCustomerBalTotalRefunded($customerBalanceRefunded);
        $order = $creditmemo->getOrder();
        $order->setData(OrderInterface::CUSTOMER_IS_GUEST, false);

        $this->creditmemoSaveAfter->execute($observer);

        $order = $this->getOrder();
        $historyComments = $this->getStoreCreditHistoryComments($order);
        self::assertEquals($expectedMessages, count($historyComments));

        foreach ($historyComments as $i => $comment) {
            self::assertStringContainsString(
                'We refunded $' . $expectedAmount[$i] . ' to Store Credit from Gift Card',
                $comment
            );
        }
    }

    /**
     * Gets variations for different refund amount from Gift Card.
     *
     * @return array
     */
    public function giftCardDataProvider(): array
    {
        return [
            [
                'refundedAmount' => 7.00,
                'customerBalanceRefunded' => 7.00,
                'expectedMessages' => 1,
                'expectedAmount' => ['7.00']
            ],
            [
                'refundedAmount' => 10.00,
                'customerBalanceRefunded' => 10.00,
                'expectedMessages' => 1,
                'expectedAmount' => ['10.00']
            ],
            [
                'refundedAmount' => 10.00,
                'customerBalanceRefunded' => 7.00,
                'expectedMessages' => 1,
                'expectedAmount' => ['7.00']
            ],
            [
                'refundedAmount' => 17.00,
                'customerBalanceRefunded' => 17.00,
                'expectedMessages' => 2,
                'expectedAmount' => ['7.00', '10.00']
            ],
            [
                'refundedAmount' => 25.00,
                'customerBalanceRefunded' => 13.00,
                'expectedMessages' => 2,
                'expectedAmount' => ['3.00', '10.00']
            ],
        ];
    }

    /**
     * Gets history comments related to refunding Gift Cards to Store Credit from the order.
     *
     * @param OrderInterface $order
     * @return array
     */
    private function getStoreCreditHistoryComments(OrderInterface $order): array
    {
        $comments = $order->getAllStatusHistory();
        $historyComments = [];
        foreach ($comments as $comment) {
            if (strpos($comment->getComment(), 'Store Credit from Gift Card') !== false) {
                $historyComments[] = $comment->getComment();
            }
        }

        return $historyComments;
    }

    /**
     * Initialize observer for tests.
     *
     * @return Observer
     */
    private function getObserver(): Observer
    {
        $creditmemo = $this->getCreditMemo(self::$orderIncrementId);

        /** @var DataObject $event */
        $event = $this->objectManager->create(DataObject::class);
        $event->setCreditmemo($creditmemo);

        /** @var Observer $observer */
        $observer = $this->objectManager->create(Observer::class);
        $observer->setEvent($event);

        return $observer;
    }

    /**
     * Gets order for test
     *
     * @return OrderInterface
     */
    private function getOrder(): OrderInterface
    {
        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        $searchCriteria = $searchCriteriaBuilder->addFilter(OrderInterface::INCREMENT_ID, self::$orderIncrementId)
            ->create();

        /** @var  OrderRepositoryInterface $orderRepository */
        $orderRepository = $this->objectManager->get(OrderRepositoryInterface::class);
        $orders = $orderRepository->getList($searchCriteria)->getItems();

        return array_pop($orders);
    }

    /**
     * Gets credit memo.
     *
     * @param string $incrementId
     * @return CreditmemoInterface
     */
    private function getCreditMemo(string $incrementId): CreditmemoInterface
    {
        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        $searchCriteria = $searchCriteriaBuilder->addFilter(CreditmemoInterface::INCREMENT_ID, $incrementId)
            ->create();

        /** @var CreditmemoRepositoryInterface $creditMemoRepository */
        $creditMemoRepository = $this->objectManager->get(CreditmemoRepositoryInterface::class);
        $creditMemoList = $creditMemoRepository->getList($searchCriteria)
            ->getItems();

        return array_pop($creditMemoList);
    }

    /**
     * Gets Gift Card by code.
     *
     * @param string $code
     * @return GiftCardAccountInterface|null
     */
    private function getGiftCardAccount(string $code)
    {
        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        $searchCriteria = $searchCriteriaBuilder->addFilter('code', $code)
            ->create();

        /** @var GiftCardAccountRepositoryInterface $repository */
        $repository = $this->objectManager->get(GiftCardAccountRepositoryInterface::class);
        $items = $repository->getList($searchCriteria)
            ->getItems();

        return array_pop($items);
    }
}
