<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CheckoutAddressSearch\Block\Checkout;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea frontend
 */
class CustomerAddressProcessorTest extends TestCase
{
    /** @var CustomerAddressProcessor */
    private $customerAddressProcessor;

    protected function setUp(): void
    {
        $this->customerAddressProcessor = Bootstrap::getObjectManager()->get(
            CustomerAddressProcessor::class
        );
    }

    /**
     * Tests that initial number of address is rendered according to config setting.
     *
     * @magentoConfigFixture default/checkout/options/address_search_page_size 1
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address.php
     * @magentoDataFixture Magento/CheckoutAddressSearch/_files/customer_with_addresses.php
     */
    public function testNumberOfAddresses(): void
    {
        $quote = $this->getQuote('test_order_1');
        $addresses = $this->customerAddressProcessor->getFormattedOptions($quote);

        $this->assertCount(
            1,
            $addresses,
            'Only 1 element expected to be on the page'
        );
    }

    /**
     * Retrieves quote by provided order ID.
     *
     * @param string $reservedOrderId
     * @return CartInterface
     */
    private function getQuote(string $reservedOrderId): CartInterface
    {
        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = Bootstrap::getObjectManager()->get(SearchCriteriaBuilder::class);
        $searchCriteria = $searchCriteriaBuilder->addFilter('reserved_order_id', $reservedOrderId)
            ->create();

        /** @var CartRepositoryInterface $quoteRepository */
        $quoteRepository = Bootstrap::getObjectManager()->get(CartRepositoryInterface::class);
        $items = $quoteRepository->getList($searchCriteria)
            ->getItems();

        return array_pop($items);
    }
}
