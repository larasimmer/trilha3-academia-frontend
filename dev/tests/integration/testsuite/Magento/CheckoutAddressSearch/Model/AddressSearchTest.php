<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CheckoutAddressSearch\Model;

use PHPUnit\Framework\TestCase;

/**
 * Class for testing customer address search on checkout.
 *
 * @magentoAppArea frontend
 */
class AddressSearchTest extends TestCase
{
    private const CUSTOMER_ID = 1;

    /** @var \Magento\CheckoutAddressSearch\Model\AddressSearch */
    private $addressSearch;

    protected function setUp(): void
    {
        $this->addressSearch = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(
            \Magento\CheckoutAddressSearch\Model\AddressSearch::class
        );
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     * @magentoDataFixture Magento/CheckoutAddressSearch/_files/customer_with_addresses.php
     */
    public function testSearchForAddressesByFirstPartOfCity(): void
    {
        $searchTerm = 'San';
        $collection = $this->addressSearch->search($searchTerm, self::CUSTOMER_ID, 1);
        $this->assertEquals(2, $collection->getSize(), 'Collection must contain exactly 2 addresses');
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     * @magentoDataFixture Magento/CheckoutAddressSearch/_files/customer_with_addresses.php
     */
    public function testSearchForAddressesBySecondPartOfCity(): void
    {
        $searchTerm = 'Francisco';
        $collection = $this->addressSearch->search($searchTerm, self::CUSTOMER_ID, 1);
        $this->assertEquals(1, $collection->getSize(), 'Collection is empty');
        /** @var \Magento\Customer\Api\Data\AddressInterface $address */
        $address = $collection->getFirstItem();
        $this->assertEquals('San Francisco', $address->getCity(), 'Found city does not match');
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     * @magentoDataFixture Magento/CheckoutAddressSearch/_files/customer_with_addresses.php
     */
    public function testSearchForAddressesByFullCityMatch(): void
    {
        $searchTerm = 'San Antonio';
        $collection = $this->addressSearch->search($searchTerm, self::CUSTOMER_ID, 1);
        $this->assertEquals(1, $collection->getSize(), 'Collection is empty');
        /** @var \Magento\Customer\Api\Data\AddressInterface $address */
        $address = $collection->getFirstItem();
        $this->assertEquals('San Antonio', $address->getCity(), 'Found city does not match');
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     * @magentoDataFixture Magento/CheckoutAddressSearch/_files/customer_with_addresses.php
     */
    public function testSearchForAddressesByZipCode(): void
    {
        $searchTerm = '75477';
        $collection = $this->addressSearch->search($searchTerm, self::CUSTOMER_ID, 1);
        $this->assertEquals(1, $collection->getSize(), 'Address by zip code not found');
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     * @magentoDataFixture Magento/CheckoutAddressSearch/_files/customer_with_addresses.php
     */
    public function testSearchForAddressesByPartOfZipCode(): void
    {
        $searchTerm = '5477';
        $collection = $this->addressSearch->search($searchTerm, self::CUSTOMER_ID, 1);
        $this->assertEquals(0, $collection->getSize(), 'Partial match is done for zip code search');
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     * @magentoDataFixture Magento/CheckoutAddressSearch/_files/customer_with_addresses.php
     */
    public function testSearchForAddressesByState(): void
    {
        $searchTerm = 'Texas';
        $collection = $this->addressSearch->search($searchTerm, self::CUSTOMER_ID, 1);
        $this->assertEquals(1, $collection->getSize(), 'Collection is empty');
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     * @magentoDataFixture Magento/CheckoutAddressSearch/_files/customer_with_addresses.php
     */
    public function testSearchForAddressesByStreet(): void
    {
        $searchTerm = 'West Cucumber';
        $collection = $this->addressSearch->search($searchTerm, self::CUSTOMER_ID, 1);
        $this->assertEquals(1, $collection->getSize(), 'Collection is empty');
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     * @magentoDataFixture Magento/CheckoutAddressSearch/_files/customer_with_addresses.php
     */
    public function testSearchForEmptyString(): void
    {
        $searchTerm = '';
        $collection = $this->addressSearch->search($searchTerm, self::CUSTOMER_ID, 1);
        $this->assertEquals(2, $collection->getSize(), 'Collection is empty');
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     * @magentoConfigFixture default/checkout/options/address_search_page_size 1
     * @magentoDataFixture Magento/CheckoutAddressSearch/_files/customer_with_addresses.php
     */
    public function testSearchPagination(): void
    {
        $searchTerm = '';

        foreach ([1, 2] as $page) {
            $collection = $this->addressSearch->search($searchTerm, self::CUSTOMER_ID, $page);
            $this->assertEquals(
                2,
                $collection->getSize(),
                'Total quantity of addresses does not match'
            );
            $this->assertEquals(
                1,
                $collection->getPageSize(),
                'Expected to have 1 element per page according to the configuration'
            );
            $this->assertCount(
                1,
                $collection->getData(),
                'Only 1 element expected to be on the page'
            );
            $this->assertEquals($page, $collection->getCurPage(), 'Incorrect current page');
            $this->assertEquals(
                2,
                $collection->getLastPageNumber(),
                'Two pages in total expected in the collection'
            );
        }
    }
}
