<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Observer;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;

/**
 * Test for converting quote customer custom attributes to order customer custom attributes
 *
 * @magentoDbIsolation enabled
 */
class CoreCopyDataQuoteToOrderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var CoreCopyDataQuoteToOrder
     */
    private $model;

    /**
     * @var Event
     */
    private $event;

    /**
     * @var Observer
     */
    private $observer;

    /**
     * @var Quote
     */
    private $quote;

    /**
     * @var Order
     */
    private $order;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $this->event = $this->objectManager->create(Event::class);
        $this->observer = $this->objectManager->create(Observer::class);
        $this->model = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
            \Magento\CustomerCustomAttributes\Observer\CoreCopyDataQuoteToOrder::class
        );
    }

    /**
     * Test for converting quote customer custom attributes to order customer custom attributes
     *
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoDataFixture Magento/CustomerCustomAttributes/_files/customer_custom_attribute.php
     * @return void
     */
    public function testExecute()
    {
        /** @var \Magento\Eav\Api\AttributeRepositoryInterface $eavRepository */
        $eavRepository = $this->objectManager->get(\Magento\Eav\Api\AttributeRepositoryInterface::class);
        $attribute = $eavRepository->get('customer', 'test_select_code');
        $selectOptions = [];
        foreach ($attribute->getOptions() as $option) {
            if ($option->getValue()) {
                $selectOptions[$option->getLabel()] = $option->getValue();
            }
        }
        $this->order = $this->objectManager->get(\Magento\Sales\Model\Order::class);
        $this->order->loadByIncrementId('100000001');
        $this->event->setData('order', $this->order);

        $this->quote = $this->getQuote('test01');
        $this->quote->setData('customer_test_select_code', $selectOptions['Second']);
        $this->event->setData('quote', $this->quote);
        $this->observer->setEvent($this->event);
        $this->model->execute($this->observer);
        $this->assertEquals($selectOptions['Second'], $this->order->getData('customer_test_select_code'));
    }

    /**
     * Gets quote by ID.
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
        /** @var CartRepositoryInterface $repository */
        $repository = $this->objectManager->get(CartRepositoryInterface::class);
        $items = $repository->getList($searchCriteria)
            ->getItems();

        return array_pop($items);
    }
}
