<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Model\Sales;

use Magento\Checkout\Model\Type\Onepage;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Reward\Model\Reward;
use Magento\Reward\Model\RewardFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Quote\Model\GetQuoteByReservedOrderId;
use PHPUnit\Framework\TestCase;

/**
 * Test for placing order with reward points.
 *
 * @magentoAppArea frontend
 * @magentoDbIsolation enabled
 */
class PlaceOrderTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var GetQuoteByReservedOrderId
     */
    private $getQuoteByReservedOrderId;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var Onepage
     */
    private $onepage;

    /**
     * @var RewardFactory
     */
    private $rewardFactory;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var OrderInterface
     */
    private $order;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager = Bootstrap::getObjectManager();
        $this->customerRepository = $this->objectManager->get(CustomerRepositoryInterface::class);
        $this->orderRepository = $this->objectManager->create(OrderRepositoryInterface::class);
        $this->getQuoteByReservedOrderId = $this->objectManager->get(GetQuoteByReservedOrderId::class);
        $this->customerSession = $this->objectManager->get(Session::class);
        $this->onepage = $this->objectManager->get(Onepage::class);
        $this->rewardFactory = $this->objectManager->get(RewardFactory::class);
        $this->registry = $this->objectManager->get(Registry::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        $this->customerSession->setCustomerId(null);
        $this->onepage->getCheckout()->clearHelperData();
        $this->onepage->getCheckout()->clearStorage();

        if ($this->order) {
            $this->registry->unregister('isSecureArea');
            $this->registry->register('isSecureArea', true);
            $this->orderRepository->delete($this->order);
            $this->registry->unregister('isSecureArea');
            $this->registry->register('isSecureArea', false);
            parent::tearDown();
        }
    }

    /**
     * @magentoDataFixture Magento/Reward/_files/customer_quote_with_reward_points.php
     * @return void
     */
    public function testPlaceOrderWithRewardPoints(): void
    {
        $customer = $this->customerRepository->get('customer@example.com');
        $this->customerSession->setCustomerId($customer->getId());
        $quote = $this->getQuoteByReservedOrderId->execute('55555555');
        $quote->collectTotals();
        $this->onepage->setQuote($quote);
        $this->onepage->saveOrder();
        $this->order = $this->orderRepository->get($this->onepage->getCheckout()->getLastOrderId());
        $this->assertEquals(5, $this->order->getExtensionAttributes()->getRewardCurrencyAmount());
        $this->assertEquals(5, $this->order->getExtensionAttributes()->getRewardPointsBalance());
        $this->assertEquals(10, $this->order->getGrandTotal());
        $reward = $this->getRewardsByCustomer($customer);
        $this->assertEquals(0, $reward->getPointsBalance());
    }

    /**
     * Loads reward data by customer.
     *
     * @param CustomerInterface $customer
     * @return Reward
     */
    private function getRewardsByCustomer(CustomerInterface $customer): Reward
    {
        $reward = $this->rewardFactory->create();
        $reward->setCustomer($customer);

        return $reward->loadByCustomer();
    }
}
