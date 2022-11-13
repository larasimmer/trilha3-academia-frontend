<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Observer;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use Magento\Multishipping\Model\Checkout\Type\Multishipping;

/**
 * Tests Gift wrapping attributes in orders created by multishipping checkout
 *
 * @magentoAppArea frontend
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OrderGiftWrappingTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var Multishipping
     */
    private $model;

    /**
     * @var AddressRepositoryInterface
     */
    private $addressRepository;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();

        $this->addressRepository = $this->objectManager->get(AddressRepositoryInterface::class);
        $this->customerRepository = $this->objectManager->get(CustomerRepositoryInterface::class);
        $orderSender = $this->getMockBuilder(OrderSender::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = $this->objectManager->create(
            Multishipping::class,
            ['orderSender' => $orderSender]
        );
    }

    /**
     * Checks a case when multiple orders with different shipping addresses are created successfully.
     *
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Multishipping/Fixtures/quote_with_split_items.php
     * @return void
     */
    public function testCreateOrders()
    {
        $quote = $this->getQuote('multishipping_quote_id');
        $billingAddress = $quote->getBillingAddress();
        $billingAddress->setGwId(1);
        $billingAddress->setGwBasePrice(100);
        $billingAddress->setGwPrice(100);
        $billingAddress->setGwBasePriceInclTax(100);
        $billingAddress->setGwPriceInclTax(100);
        $quoteRepository = $this->objectManager->get(CartRepositoryInterface::class);
        $quote->setTotalsCollectedFlag(false);
        $quote->collectTotals();
        $quoteRepository->save($quote);

        /** @var CheckoutSession $session */
        $session = $this->objectManager->get(CheckoutSession::class);
        $session->replaceQuote($quote);

        $this->model->createOrders();

        $orderList = $this->getOrderList((int)$quote->getId());

        $firstOrder = array_shift($orderList);

        $this->assertEquals($firstOrder->getGwId(), 1);
        $this->assertEquals($firstOrder->getGwBasePrice(), 100);
        $this->assertEquals($firstOrder->getGwPrice(), 100);
        $this->assertEquals($firstOrder->getGwBasePriceInclTax(), 100);
        $this->assertEquals($firstOrder->getGwPriceInclTax(), 100);
    }

    /**
     * Retrieves quote by reserved order id.
     *
     * @param string $reservedOrderId
     * @return Quote
     */
    private function getQuote(string $reservedOrderId): Quote
    {
        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        $searchCriteria = $searchCriteriaBuilder->addFilter('reserved_order_id', $reservedOrderId)
            ->create();

        /** @var CartRepositoryInterface $quoteRepository */
        $quoteRepository = $this->objectManager->get(CartRepositoryInterface::class);
        $items = $quoteRepository->getList($searchCriteria)->getItems();

        return array_pop($items);
    }

    /**
     * Get list of orders by quote id.
     *
     * @param int $quoteId
     * @return array
     */
    private function getOrderList(int $quoteId): array
    {
        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        $searchCriteria = $searchCriteriaBuilder->addFilter('quote_id', $quoteId)
            ->create();

        /** @var OrderRepositoryInterface $orderRepository */
        $orderRepository = $this->objectManager->get(OrderRepositoryInterface::class);

        return $orderRepository->getList($searchCriteria)->getItems();
    }
}
