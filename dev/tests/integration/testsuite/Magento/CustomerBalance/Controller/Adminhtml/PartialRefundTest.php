<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerBalance\Controller\Adminhtml;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Message\MessageInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * @magentoAppArea adminhtml
 * @magentoDataFixture Magento/CustomerBalance/Fixtures/creditmemo_customer_balance.php
 */
class PartialRefundTest extends AbstractBackendController
{
    /**
     * Checks a case when order is placed with `Store Credit` usage and refund can be created
     * for invoice and Customer Balance
     */
    public function testMultipleRefunds()
    {
        $total = 50.00;
        $order = $this->getOrder('100000002');

        $input = [
            'form_key' => $this->getFormKey(),
            'creditmemo' => [
                'do_offline' => 1,
                'refund_customerbalance_return_enable' => 1,
                'refund_customerbalance_return' => $total
            ]
        ];
        $this->getRequest()->setMethod('POST');
        $this->getRequest()->setPostValue($input);
        $this->dispatch('backend/sales/order_creditmemo/save/order_id/' . $order->getId());

        $this->assertSessionMessages(
            self::equalTo(['You created the credit memo.']),
            MessageInterface::TYPE_SUCCESS
        );

        $updatedOrder = $this->getOrder('100000002');
        self::assertFalse($updatedOrder->canCreditmemo());
        self::assertEquals($total, $updatedOrder->getBaseCustomerBalanceRefunded());
    }

    /**
     * Gets order by increment ID.
     *
     * @param string $incrementId
     * @return Order
     */
    private function getOrder(string $incrementId): Order
    {
        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->_objectManager->get(SearchCriteriaBuilder::class);
        $searchCriteria = $searchCriteriaBuilder->addFilter('increment_id', $incrementId)
            ->create();

        /** @var OrderRepositoryInterface $repository */
        $repository = $this->_objectManager->get(OrderRepositoryInterface::class);
        $items = $repository->getList($searchCriteria)
            ->getItems();

        return array_pop($items);
    }

    /**
     * Gets form key.
     *
     * @return string
     */
    private function getFormKey(): string
    {
        /** @var FormKey $formKey */
        $formKey = $this->_objectManager->get(FormKey::class);
        return $formKey->getFormKey();
    }
}
