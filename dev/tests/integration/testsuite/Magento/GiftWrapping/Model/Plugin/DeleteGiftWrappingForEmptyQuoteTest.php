<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftWrapping\Model\Plugin;

use Magento\TestFramework\Helper\Bootstrap;
use Magento\Quote\Model\Quote;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Catalog\Api\ProductRepositoryInterface;

/**
 * Test order totals with gift wrapping
 */
class DeleteGiftWrappingForEmptyQuoteTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->quoteRepository = $this->objectManager->get(CartRepositoryInterface::class);
        $this->productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
    }

    /**
     * @magentoDataFixture Magento/GiftWrapping/_files/quote/quote_with_selected_gift_wrapping.php
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoAppArea frontend
     */
    public function testGetGiftWrappingInfo()
    {
        $quote = $this->getQuote('test_quote_with_selected_gift_wrapping');
        $item = $quote->getItems()[0];
        $quote->removeItem($item->getData('item_id'));
        $this->assertEquals($quote->getGwId(), null);
        $this->assertEquals($quote->getGwBasePrice(), 0);
        $this->assertEquals($quote->getGwPrice(), 0);
    }

    /**
     * Loads quote by order increment id.
     *
     * @param string $orderIncrementId
     * @return Quote
     */
    private function getQuote(string $orderIncrementId): Quote
    {
        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        $searchCriteria = $searchCriteriaBuilder->addFilter('reserved_order_id', $orderIncrementId)
            ->create();

        $items = $this->quoteRepository->getList($searchCriteria)
            ->getItems();

        return reset($items);
    }
}
