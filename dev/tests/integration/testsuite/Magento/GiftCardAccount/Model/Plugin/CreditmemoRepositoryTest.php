<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardAccount\Model\Plugin;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\ObjectManagerInterface;
use Magento\GiftCardAccount\Api\GiftCardAccountRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Sales\Api\CreditmemoManagementInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Invoice;
use Magento\GiftCardAccount\Model\ResourceModel\Pool;
use PHPUnit\Framework\TestCase;

/**
 * Class for test refund observer
 */
class CreditmemoRepositoryTest extends TestCase
{
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
     * @magentoDataFixture Magento/GiftCard/_files/invoice_with_gift_card.php
     */
    public function testRefundOnlineWithGiftCardAccountByInvoice(): void
    {
        $codes = [];
        $qty = 0;
        $order = $this->getOrder();
        $orderItems = $order->getItems();

        /** @var Pool $poolResourceModel */
        $poolResourceModel = $this->objectManager->get(Pool::class);
        $poolResourceModel->saveCode('fixture_code_1');
        $poolResourceModel->saveCode('fixture_code_2');

        $invoiceItems = $order->getInvoiceCollection()
            ->getItems();
        /** @var Invoice $invoice */
        $invoice = array_pop($invoiceItems);
        /** @var CreditmemoFactory $creditmemoFactory */
        $creditmemoFactory = $this->objectManager->get(CreditmemoFactory::class);
        /** @var  Creditmemo $creditmemo */
        $creditmemo = $creditmemoFactory->createByInvoice($invoice, $order->getData());
        $creditmemo->setOrder($order);
        $creditmemo->setState(Creditmemo::STATE_REFUNDED);
        $creditmemo->setIncrementId(self::ORDER_INCREMENT_ID);

        foreach ($creditmemo->getItems() as $creditMemoItem) {
            $orderItem = $orderItems[$creditMemoItem->getOrderItemId()];
            if ($orderItem->getProductOptionByCode('giftcard_created_codes')) {
                $codes = $orderItem->getProductOptionByCode('giftcard_created_codes');
                $qty = abs((int)$creditMemoItem->getQty());
            }
        }

        $giftCardAccounts = $this->getGiftCardAccount($codes);
        $creditmemoManagement = $this->objectManager->get(CreditmemoManagementInterface::class);
        $creditmemoManagement->refund($creditmemo);

        $existsGiftCardAccounts = $this->getGiftCardAccount($codes);

        self::assertEquals(count($giftCardAccounts) - $qty, count($existsGiftCardAccounts));
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

    /**
     * Gets Gift Card by code.
     *
     * @param array $codes
     * @return array
     */
    private function getGiftCardAccount(array $codes): array
    {
        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        $searchCriteria = $searchCriteriaBuilder->addFilter('code', $codes, 'in')
            ->create();

        /** @var GiftCardAccountRepositoryInterface $repository */
        $repository = $this->objectManager->get(GiftCardAccountRepositoryInterface::class);
        $items = $repository->getList($searchCriteria)
            ->getItems();

        return $items;
    }
}
