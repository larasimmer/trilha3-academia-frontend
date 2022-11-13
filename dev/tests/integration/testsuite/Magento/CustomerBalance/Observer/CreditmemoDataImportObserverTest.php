<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerBalance\Observer;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Event\Observer;
use Magento\Framework\DataObject;

/**
 * @magentoDataFixture  Magento/CustomerBalance/_files/creditmemo_with_customer_balance.php
 */
class CreditmemoDataImportObserverTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var CreditmemoDataImportObserver
     */
    private $observer;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->observer = $this->objectManager->create(CreditmemoDataImportObserver::class);
    }

    /**
     * Checks a case when Store Credit refund value has been changed manually.
     *
     * @param float $total
     * @param float $refundBalance
     * @param float $expTotal
     * @param float $expBalanced
     * @param bool $expAllowZeroTotal
     * @throws \Magento\Framework\Exception\LocalizedException
     * @dataProvider totalsDataProvider
     */
    public function testRefundBalanceInitialization(
        float $total,
        float $refundBalance,
        float $expTotal,
        float $expBalance,
        bool $expAllowZeroTotal
    ) {
        $input = [
            'refund_customerbalance_return_enable' => 1,
            'refund_customerbalance_return' => $refundBalance
        ];
        $creditMemo = $this->getCreditMemo('100000001');
        $creditMemo->setBaseGrandTotal($total)
            ->setBaseCustomerBalanceReturnMax(80.00);
        $observer = $this->getObserver($creditMemo, $input);
        $this->observer->execute($observer);

        self::assertEquals($expTotal, $creditMemo->getBaseGrandTotal(), 'Credit Memo grand total should match.');
        self::assertEquals($expBalance, $creditMemo->getBsCustomerBalTotalRefunded(), 'Refunded balance should match.');
        self::assertEquals($expAllowZeroTotal, $creditMemo->getAllowZeroGrandTotal());
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
                'total' => 0.00,
                'refundBalance' => 16.47,
                'expTotal' => 0.00,
                'expBalance' => 16.47,
                'expAllowZeroTotal' => false,
            ],
            [
                'total' => 16.47,
                'refundBalance' => 16.47,
                'expTotal' => 16.47,
                'expBalance' => 16.47,
                'expAllowZeroTotal' => false,
            ],
            [
                'total' => 16.47,
                'refundBalance' => 15.12,
                'expTotal' => 16.47,
                'expBalance' => 15.12,
                'expAllowZeroTotal' => false,
            ]
        ];
    }

    /**
     * Creates stub for observer.
     *
     * @param CreditmemoInterface $creditMemo
     * @param array $input
     * @return Observer
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getObserver(CreditmemoInterface $creditMemo, array $input): Observer
    {
        /** @var DataObject $event */
        $event = $this->objectManager->create(DataObject::class);
        $event->setCreditmemo($creditMemo)
            ->setInput($input);

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
}
