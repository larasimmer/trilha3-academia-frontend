<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\GraphQl\Rma;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\GraphQl\GetCustomerAuthenticationHeader;
use Magento\Rma\Api\Data\RmaInterface;
use Magento\Rma\Api\RmaRepositoryInterface;
use Magento\Rma\Model\Rma;
use Magento\Rma\Model\Rma\Source\Status;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test for customer returns query
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CustomerReturnsTest extends GraphQlAbstract
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var GetCustomerAuthenticationHeader
     */
    private $getCustomerAuthenticationHeader;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var RmaRepositoryInterface
     */
    private $rmaRepository;

    /**
     * @var Uid
     */
    private $idEncoder;

    /**
     * Setup
     */
    public function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->getCustomerAuthenticationHeader = $this->objectManager->get(GetCustomerAuthenticationHeader::class);
        $this->customerRepository = $this->objectManager->get(CustomerRepositoryInterface::class);
        $this->searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        $this->rmaRepository = $this->objectManager->get(RmaRepositoryInterface::class);
        $this->idEncoder = $this->objectManager->get(Uid::class);
    }

    /**
     * Test customer returns
     *
     * @magentoConfigFixture sales/magento_rma/use_store_address 1
     * @magentoConfigFixture shipping/origin/name test
     * @magentoConfigFixture shipping/origin/phone +380003434343
     * @magentoConfigFixture shipping/origin/street_line1 street
     * @magentoConfigFixture shipping/origin/street_line2 1
     * @magentoConfigFixture shipping/origin/city Montgomery
     * @magentoConfigFixture shipping/origin/region_id 1
     * @magentoConfigFixture shipping/origin/postcode 12345
     * @magentoConfigFixture shipping/origin/country_id US
     * @magentoConfigFixture default_store sales/magento_rma/enabled 1
     * @magentoApiDataFixture Magento/Rma/_files/rma_with_customer.php
     */
    public function testCustomerReturnsQuery()
    {
        $customerEmail = 'customer_uk_address@test.com';
        $pageSize = 10;
        $currentPage = 1;

        $query = <<<QUERY
{
  customer {
    returns(pageSize: {$pageSize}, currentPage: {$currentPage}) {
      items {
        uid
        number
        created_at
        customer{firstname lastname email}
        status
      }
      page_info {
        current_page
        page_size
        total_pages
      }
      total_count
    }
  }
}
QUERY;

        $response = $this->graphQlQuery(
            $query,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute($customerEmail, 'password')
        );

        $customer = $this->customerRepository->get($customerEmail);
        $rma = $this->getCustomerReturn($customerEmail);

        self::assertEquals($rma->getDateRequested(), $response['customer']['returns']['items'][0]['created_at']);
        self::assertEquals($rma->getIncrementId(), $response['customer']['returns']['items'][0]['number']);
        self::assertEqualsIgnoringCase(
            Status::STATE_APPROVED,
            $response['customer']['returns']['items'][0]['status']
        );
        self::assertEquals(
            $customer->getFirstname() ,
            $response['customer']['returns']['items'][0]['customer']['firstname']
        );
        self::assertEquals(
            $customer->getLastname(),
            $response['customer']['returns']['items'][0]['customer']['lastname']
        );
        self::assertEquals($customerEmail,
            $response['customer']['returns']['items'][0]['customer']['email']
        );
        self::assertEquals(
            $this->idEncoder->encode((string)$rma->getEntityId()),
            $response['customer']['returns']['items'][0]['uid']
        );
        self::assertEquals($currentPage, $response['customer']['returns']['page_info']['current_page']);
        self::assertEquals($pageSize, $response['customer']['returns']['page_info']['page_size']);
        self::assertEquals(1, $response['customer']['returns']['page_info']['total_pages']);
        self::assertEquals(1, $response['customer']['returns']['total_count']);
    }

    /**
     * Test customer returns with negative page size value
     *
     * @magentoConfigFixture default_store sales/magento_rma/enabled 1
     * @magentoApiDataFixture Magento/Rma/_files/rma_with_customer.php
     */
    public function testCustomerReturnsQueryWithNegativePageSize()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('pageSize value must be greater than 0.');

        $query = <<<QUERY
{
  customer {
    returns(pageSize: -5, currentPage: 2) {
      items {
        uid
        number
        created_at
        customer{firstname lastname email}
        status
      }
      page_info {
        current_page
        page_size
        total_pages
      }
      total_count
    }
  }
}
QUERY;

        $this->graphQlQuery(
            $query,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute('customer_uk_address@test.com', 'password')
        );
    }

    /**
     * Test customer returns with zero page size value
     *
     * @magentoConfigFixture default_store sales/magento_rma/enabled 1
     * @magentoApiDataFixture Magento/Rma/_files/rma_with_customer.php
     */
    public function testCustomerReturnsQueryWithZeroPageSize()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('pageSize value must be greater than 0.');

        $query = <<<QUERY
{
  customer {
    returns(pageSize: 0, currentPage: 1) {
      items {
        uid
        number
        created_at
        customer{firstname lastname email}
        status
      }
      page_info {
        current_page
        page_size
        total_pages
      }
      total_count
    }
  }
}
QUERY;

        $this->graphQlQuery(
            $query,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute('customer_uk_address@test.com', 'password')
        );
    }

    /**
     * Test customer returns with zero current page value
     *
     * @magentoConfigFixture default_store sales/magento_rma/enabled 1
     * @magentoApiDataFixture Magento/Rma/_files/rma_with_customer.php
     */
    public function testCustomerReturnsQueryWithZeroCurrentPage()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('currentPage value must be greater than 0.');

        $query = <<<QUERY
{
  customer {
    returns(pageSize: 10, currentPage: 0) {
      items {
        uid
        number
        created_at
        customer{firstname lastname email}
        status
      }
      page_info {
        current_page
        page_size
        total_pages
      }
      total_count
    }
  }
}
QUERY;

        $this->graphQlQuery(
            $query,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute('customer_uk_address@test.com', 'password')
        );
    }

    /**
     * Test customer returns with negative current page value
     *
     * @magentoConfigFixture default_store sales/magento_rma/enabled 1
     * @magentoApiDataFixture Magento/Rma/_files/rma_with_customer.php
     */
    public function testCustomerReturnsQueryWithNegativeCurrentPage()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('currentPage value must be greater than 0.');

        $query = <<<QUERY
{
  customer {
    returns(pageSize: 10, currentPage: -1) {
      items {
        uid
        number
        created_at
        customer{firstname lastname email}
        status
      }
      page_info {
        current_page
        page_size
        total_pages
      }
      total_count
    }
  }
}
QUERY;

        $this->graphQlQuery(
            $query,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute('customer_uk_address@test.com', 'password')
        );
    }

    /**
     * Test customer returns query with unauthorized customer
     *
     * @magentoConfigFixture default_store sales/magento_rma/enabled 1
     */
    public function testCustomerReturnsQueryWithUnauthorizedCustomer()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The current customer isn\'t authorized.');

        $query = <<<QUERY
{
  customer {
    returns(pageSize: 10, currentPage: 1) {
      items {
        uid
        number
        created_at
        customer{firstname lastname email}
        status
      }
      page_info {
        current_page
        page_size
        total_pages
      }
      total_count
    }
  }
}
QUERY;

        $this->graphQlQuery($query);
    }

    /**
     * Test customer returns query without params
     *
     * @magentoConfigFixture default_store sales/magento_rma/enabled 1
     * @magentoConfigFixture sales/magento_rma/use_store_address 1
     * @magentoConfigFixture shipping/origin/name test
     * @magentoConfigFixture shipping/origin/phone +380003434343
     * @magentoConfigFixture shipping/origin/street_line1 street
     * @magentoConfigFixture shipping/origin/street_line2 1
     * @magentoConfigFixture shipping/origin/city Montgomery
     * @magentoConfigFixture shipping/origin/region_id 1
     * @magentoConfigFixture shipping/origin/postcode 12345
     * @magentoConfigFixture shipping/origin/country_id US
     * @magentoApiDataFixture Magento/Rma/_files/rma_with_customer.php
     */
    public function testCustomerReturnsQueryWithoutParams()
    {
        $customerEmail = 'customer_uk_address@test.com';
        $query = <<<QUERY
{
  customer {
    returns {
      items {
        uid
        number
        created_at
        customer{firstname lastname email}
        status
      }
      page_info {
        current_page
        page_size
        total_pages
      }
      total_count
    }
  }
}
QUERY;

        $response = $this->graphQlQuery(
            $query,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute($customerEmail, 'password')
        );

        $customer = $this->customerRepository->get($customerEmail);
        $rma = $this->getCustomerReturn($customerEmail);

        self::assertEquals($rma->getDateRequested(), $response['customer']['returns']['items'][0]['created_at']);
        self::assertEquals($rma->getIncrementId(), $response['customer']['returns']['items'][0]['number']);
        self::assertEqualsIgnoringCase(
            Status::STATE_APPROVED,
            $response['customer']['returns']['items'][0]['status']
        );
        self::assertEquals(
            $customer->getFirstname() ,
            $response['customer']['returns']['items'][0]['customer']['firstname']
        );
        self::assertEquals(
            $customer->getLastname(),
            $response['customer']['returns']['items'][0]['customer']['lastname']
        );
        self::assertEquals($customerEmail,
            $response['customer']['returns']['items'][0]['customer']['email']
        );
        self::assertEquals(
            $this->idEncoder->encode((string)$rma->getEntityId()),
            $response['customer']['returns']['items'][0]['uid']
        );
        self::assertEquals(1, $response['customer']['returns']['page_info']['current_page']);
        self::assertEquals(20, $response['customer']['returns']['page_info']['page_size']);
        self::assertEquals(1, $response['customer']['returns']['page_info']['total_pages']);
        self::assertEquals(1, $response['customer']['returns']['total_count']);
    }

    /**
     * Test customer returns query with disabled RMA
     *
     * @magentoApiDataFixture Magento/Rma/_files/rma_with_customer.php
     * @magentoConfigFixture default_store sales/magento_rma/enabled 0
     */
    public function testCustomerReturnsQueryWithDisabledRma()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('RMA is disabled.');

        $query = <<<QUERY
{
  customer {
    returns(pageSize: 10, currentPage: 0) {
      items {
        uid
        number
        created_at
        customer{firstname lastname email}
        status
      }
      page_info {
        current_page
        page_size
        total_pages
      }
      total_count
    }
  }
}
QUERY;

        $this->graphQlQuery(
            $query,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute('customer_uk_address@test.com', 'password')
        );
    }

    /**
     * Get customer return
     *
     * @param string $customerEmail
     * @return RmaInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getCustomerReturn(string $customerEmail): RmaInterface
    {
        $customer = $this->customerRepository->get($customerEmail);
        $this->searchCriteriaBuilder->addFilter(Rma::CUSTOMER_ID, $customer->getId());
        $searchResults = $this->rmaRepository->getList($this->searchCriteriaBuilder->create());

        return $searchResults->getFirstItem();
    }
}
