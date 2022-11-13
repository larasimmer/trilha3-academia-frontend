<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardAccount\Observer;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ObjectManager;
use Magento\GiftCardAccount\Api\GiftCardAccountRepositoryInterface;
use Magento\GiftCardAccount\Model\History;
use Magento\GiftCardAccount\Model\ResourceModel\History\CollectionFactory as HistoryCollectionFactory;
use Magento\GiftCardAccount\Test\Handler\GiftCardAccount\GiftCardAccountInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard as ProductGiftCard;

class GiftCardAccountSaveAfterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * Tests that giftcard account history contains info about initial order.
     *
     * @magentoDataFixture Magento/GiftCardAccount/_files/codes_pool.php
     * @magentoDataFixture Magento/GiftCard/_files/gift_card.php
     * @magentoDataFixture Magento/GiftCard/_files/invoice_with_gift_card.php
     */
    public function testGiftCardAccountHistory()
    {
        $order = $this->getOrder();

        /** @var Item $orderItem */
        $giftCardItem = $this->getGiftcardItem($order);
        $giftCardItemOptions = $giftCardItem->getProductOptions();
        $giftCardCreatedCodes = $giftCardItemOptions['giftcard_created_codes'];

        $this->assertCount(2, $giftCardCreatedCodes);

        foreach ($giftCardCreatedCodes as $code) {
            $giftCardAccount = $this->getGiftCardAccount($code);
            $historyItem = $this->getHistoryItem(
                (int)$giftCardAccount->getId()
            );

            $this->assertNotNull($historyItem);
            $this->assertStringContainsString(
                $order->getIncrementId(),
                $historyItem->getAdditionalInfo(),
                'Giftcard account history should contain initial order number'
            );
            $this->assertEquals(
                History::ACTION_CREATED,
                $historyItem->getAction(),
                'Giftcard account history should contain info about creation'
            );
        }
    }

    /**
     * Returns giftcard item from order.
     *
     * @param Order $order
     * @return OrderItemInterface|null
     */
    private function getGiftcardItem(Order $order)
    {
        foreach ($order->getItems() as $item) {
            if ($item->getProductType() === ProductGiftCard::TYPE_GIFTCARD) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Get stored order.
     * @return Order
     */
    private function getOrder()
    {
        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        $searchCriteria = $searchCriteriaBuilder->addFilter(OrderInterface::INCREMENT_ID, '100000001')
            ->create();

        $orderRepository = $this->objectManager->get(OrderRepositoryInterface::class);
        $orders = $orderRepository->getList($searchCriteria)
            ->getItems();

        /** @var OrderInterface $order */
        return array_pop($orders);
    }

    /**
     * Returns first gift card account history item.
     *
     * @param int $giftCardAccountId
     * @return History|null
     */
    private function getHistoryItem(int $giftCardAccountId)
    {
        $collectionFactory = $this->objectManager->create(HistoryCollectionFactory::class);

        /** @var \Magento\GiftCardAccount\Model\ResourceModel\History\Collection $collection */
        $collection = $collectionFactory->create();
        $collection->addFieldToFilter('giftcardaccount_id', $giftCardAccountId);
        $historyItems = $collection->getItems();

        return array_pop($historyItems);
    }

    /**
     * Returns gift card by code.
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
