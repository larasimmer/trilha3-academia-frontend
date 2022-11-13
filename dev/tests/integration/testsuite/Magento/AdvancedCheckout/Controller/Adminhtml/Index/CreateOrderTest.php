<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Controller\Adminhtml\Index;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * 'Create Order' Controller integration tests.
 *
 * @magentoAppArea adminhtml
 */
class CreateOrderTest extends AbstractBackendController
{
    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->quoteRepository = $this->_objectManager->get(CartRepositoryInterface::class);
    }

    /**
     * Test that items of active Quote are deleted after creating new Quote.
     *
     * @return void
     * @magentoDataFixture Magento/Sales/_files/quote_with_two_products_and_customer.php
     */
    public function testQuoteItemsAreDeletedAfterCreatingNewQuote(): void
    {
        $activeQuote = $this->quoteRepository->getActiveForCustomer(1);
        $this->assertTrue($activeQuote->hasItems());
        $this->assertNotEquals(0, $activeQuote->getGrandTotal());

        $this->getRequest()->setParams([
            'customer' => 1,
            'store' => 1,
        ]);
        $this->dispatch('backend/checkout/index/createOrder');

        $this->assertFalse($activeQuote->hasItems());
        $this->assertEquals(0, $activeQuote->getGrandTotal());

        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->_objectManager->create(SearchCriteriaBuilder::class);
        $searchCriteriaBuilder
            ->addFilter('customer_id', 1)
            ->addFilter('main_table.' . CartInterface::KEY_IS_ACTIVE, 0);
        $searchResult = $this->quoteRepository->getList($searchCriteriaBuilder->create());

        $this->assertEquals(1, $searchResult->getTotalCount());
        $newQuote = current($searchResult->getItems());
        $this->assertTrue($newQuote->hasItems());
    }
}
