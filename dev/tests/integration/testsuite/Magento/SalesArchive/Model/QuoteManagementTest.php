<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesArchive\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class for testing QuoteManagement model with SalesArchive.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class QuoteManagementTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Place order using payment action "Sale" and check that this order
     * is not present in order archive grid.
     *
     * @magentoConfigFixture current_store sales/magento_salesarchive/active 0
     * @magentoConfigFixture current_store payment/payflowpro/active 1
     * @magentoConfigFixture current_store payment/payflowpro/payment_action Sale
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Sales/_files/quote_with_bundle.php
     *
     * @return void
     */
    public function testPlacedOrderIsNotInArchiveGrid(): void
    {
        $objectManager = Bootstrap::getObjectManager();

        $objectManager->addSharedInstance(
            $this->getHttpClientMock(),
            \Magento\Paypal\Model\Payflow\Service\Gateway::class
        );

        $quote = $this->getQuote('test01');
        $quote->getPayment()->setMethod(\Magento\Paypal\Model\Config::METHOD_PAYFLOWPRO);

        /** @var CartRepositoryInterface $quoteRepository */
        $quoteRepository = $objectManager->get(CartRepositoryInterface::class);
        $quote->collectTotals();
        $quoteRepository->save($quote);

        /** Execute SUT */
        /** @var \Magento\Quote\Api\CartManagementInterface $model */
        $cartManagement = $objectManager->create(\Magento\Quote\Api\CartManagementInterface::class);
        /** @var \Magento\Sales\Api\OrderRepositoryInterface $orderRepository */
        $orderRepository = $objectManager->create(\Magento\Sales\Api\OrderRepositoryInterface::class);
        $orderId = $cartManagement->placeOrder($quote->getId());
        $order = $orderRepository->get($orderId);

        /** Check if SUT caused expected effects */
        $orderItems = $order->getItems();
        $this->assertCount(3, $orderItems);

        /** @var \Magento\SalesArchive\Model\ResourceModel\Archive $archive */
        $archive = $objectManager->create(\Magento\SalesArchive\Model\ResourceModel\Archive::class);

        $this->assertFalse(
            $archive->isOrderInArchive($order->getEntityId()),
            'Order must not be present in orders archive'
        );
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
        $searchCriteriaBuilder = Bootstrap::getObjectManager()->get(SearchCriteriaBuilder::class);
        $searchCriteria = $searchCriteriaBuilder->addFilter('reserved_order_id', $reservedOrderId)
            ->create();

        /** @var CartRepositoryInterface $quoteRepository */
        $quoteRepository = Bootstrap::getObjectManager()->get(CartRepositoryInterface::class);
        $items = $quoteRepository->getList($searchCriteria)->getItems();

        return array_pop($items);
    }
    /**
     * Get HTTP Client for payment.
     *
     * @return MockObject
     */
    private function getHttpClientMock(): MockObject
    {
        $gatewayMock = $this->getMockBuilder(\Magento\Paypal\Model\Payflow\Service\Gateway::class)
            ->disableOriginalConstructor()
            ->setMethods(['postRequest'])
            ->getMock();

        $gatewayMock
            ->method('postRequest')
            ->willReturn(
                new \Magento\Framework\DataObject(
                    [
                        'result' => '0',
                        'pnref' => 'A70AAC2378BA',
                        'respmsg' => 'Approved',
                        'authcode' => '647PNI',
                        'avsaddr' => 'Y',
                        'avszip' => 'N',
                        'hostcode' => 'A',
                        'procavs' => 'A',
                        'visacardlevel' => '12',
                        'transtime' => '2019-06-24 10:12:03',
                        'firstname' => 'John',
                        'lastname' => 'Doe',
                        'amt' => '14.99',
                        'acct' => '1111',
                        'expdate' => '0221',
                        'cardtype' => '0',
                        'iavs' => 'N',
                        'result_code' => '0',
                    ]
                )
            );
        return $gatewayMock;
    }
}
