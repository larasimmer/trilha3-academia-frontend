<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerBalance\Observer;

use Magento\CustomerBalance\Model\Balance;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Test for \Magento\CustomerBalance\Observer\CreditmemoSaveAfterObserver.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @magentoDataFixture Magento/Reward/_files/rate.php
 * @magentoDataFixture Magento/CustomerBalance/_files/creditmemo_with_customer_balance.php
 */
class CreditmemoSaveAfterObserverTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var CreditmemoSaveAfterObserver
     */
    private $observer;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->observer = $this->objectManager->create(CreditmemoSaveAfterObserver::class);
        $this->orderFactory = $this->objectManager->create(OrderFactory::class);
    }

    /**
     * Checks a case when entered balance is allowed to perform refund.
     *
     * @return void
     * @throws LocalizedException
     */
    public function testExecute(): void
    {
        $maxAllowedBalance = 66.48;
        $customerBalance = 28.53;
        $rewardPoints = 0;
        $creditMemo = $this->getCreditMemo('100000001');
        $creditMemo->setBaseCustomerBalanceReturnMax($maxAllowedBalance)
            ->setBsCustomerBalTotalRefunded($customerBalance)
            ->setRewardPointsBalanceRefund($rewardPoints)
            ->setBaseCustomerBalanceRefunded($customerBalance)
            ->setCustomerBalanceRefunded($customerBalance)
            ->setCustomerBalanceRefundFlag(true);
        $observer = $this->getObserver($creditMemo);
        $this->observer->execute($observer);

        $balance = $this->getCustomerBalance((int)$creditMemo->getOrder()->getCustomerId());
        $this->assertEquals($customerBalance, $balance->getAmount());

        $order = $this->getOrder('100000001');
        $this->assertEquals($customerBalance, $order->getBaseCustomerBalanceRefunded());
        $this->assertEquals($customerBalance, $order->getCustomerBalanceRefunded());
    }

    /**
     * Checks a case when the entered Customer Balance or Reward Points greater then allowed Balance.
     *
     * @return void
     */
    public function testExecuteWithNotAllowedBalance(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('You can\'t use more store credit than the order amount.');

        $maxAllowedBalance = 66.48;
        $customerBalance = 28.53;
        $rewardPoints = 10000;
        $creditMemo = $this->getCreditMemo('100000001');
        $creditMemo->setBaseCustomerBalanceReturnMax($maxAllowedBalance)
            ->setBsCustomerBalTotalRefunded($customerBalance)
            ->setRewardPointsBalanceRefund($rewardPoints);
        $observer = $this->getObserver($creditMemo);
        $this->observer->execute($observer);
    }

    /**
     * Returns order by increment id
     *
     * @param string $incrementId
     * @return OrderInterface
     */
    private function getOrder(string $incrementId): OrderInterface
    {
        $order = $this->orderFactory->create();
        $order->loadByIncrementId($incrementId);

        return $order;
    }

    /**
     * Creates stub for observer.
     *
     * @param CreditmemoInterface $creditMemo
     * @return Observer
     * @throws LocalizedException
     */
    private function getObserver(CreditmemoInterface $creditMemo): Observer
    {
        /** @var DataObject $event */
        $event = $this->objectManager->create(DataObject::class);
        $event->setCreditmemo($creditMemo);

        /** @var Observer $observer */
        $observer = $this->objectManager->create(Observer::class);
        $observer->setEvent($event);

        return $observer;
    }

    /**
     * Gets Credit Memo by increment ID.
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
     * Gets Customer Balance entity by the customer.
     *
     * @param int $customerId
     * @return Balance
     */
    private function getCustomerBalance(int $customerId): Balance
    {
        /** @var Balance $customerBalance */
        $customerBalance = $this->objectManager->create(Balance::class);
        $customerBalance->setCustomerId($customerId);
        $customerBalance->loadByCustomer();

        return $customerBalance;
    }
}
