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
use Magento\Framework\ObjectManagerInterface;
use Magento\GraphQl\GetCustomerAuthenticationHeader;
use Magento\Rma\Api\Data\RmaInterface;
use Magento\Rma\Api\RmaRepositoryInterface;
use Magento\Rma\Model\Rma;
use Magento\Rma\Model\Rma\Source\Status;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test for customer orders query with returns
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CustomerOrdersWithReturnsTest extends GraphQlAbstract
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var GetCustomerAuthenticationHeader
     */
    private $getCustomerAuthenticationHeader;

    /**
     * @var OrderInterfaceFactory
     */
    private $orderFactory;

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
        $this->customerRepository = $this->objectManager->get(CustomerRepositoryInterface::class);
        $this->searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        $this->orderFactory = $this->objectManager->get(OrderInterfaceFactory::class);
        $this->getCustomerAuthenticationHeader = $this->objectManager->get(GetCustomerAuthenticationHeader::class);
        $this->rmaRepository = $this->objectManager->get(RmaRepositoryInterface::class);
        $this->idEncoder = $this->objectManager->get(Uid::class);
    }

    /**
     * Test customer order returns
     *
     * @magentoApiDataFixture Magento/Rma/_files/rma_with_customer.php
     * @magentoConfigFixture sales/magento_rma/enabled 1
     * @magentoConfigFixture sales/magento_rma/use_store_address 1
     * @magentoConfigFixture shipping/origin/name test
     * @magentoConfigFixture shipping/origin/phone +380003434343
     * @magentoConfigFixture shipping/origin/street_line1 street
     * @magentoConfigFixture shipping/origin/street_line2 1
     * @magentoConfigFixture shipping/origin/city Montgomery
     * @magentoConfigFixture shipping/origin/region_id 1
     * @magentoConfigFixture shipping/origin/postcode 12345
     * @magentoConfigFixture shipping/origin/country_id US
     */
    public function testCustomerOrdersWithReturnsQuery()
    {
        $customerEmail = 'customer_uk_address@test.com';
        $pageSize = 10;
        $currentPage = 1;
        $orderIncrementId = '100000555';

        $query = <<<QUERY
{
  customer {
    orders(filter: {number: {eq: "{$orderIncrementId}"}}) {
      items {
        returns(pageSize: {$pageSize}, currentPage: {$currentPage}) {
          items {
            uid
            created_at
            customer{firstname lastname email}
            status
            number
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
        $rma = $this->getCustomerReturnByOrder($customerEmail, $orderIncrementId);

        self::assertEquals(
            $rma->getDateRequested(),
            $response['customer']['orders']['items'][0]['returns']['items'][0]['created_at']
        );
        self::assertEquals(
            $rma->getIncrementId(),
            $response['customer']['orders']['items'][0]['returns']['items'][0]['number']
        );
        self::assertEqualsIgnoringCase(
            Status::STATE_APPROVED,
            $response['customer']['orders']['items'][0]['returns']['items'][0]['status']
        );
        self::assertEquals(
            $customer->getFirstname(),
            $response['customer']['orders']['items'][0]['returns']['items'][0]['customer']['firstname']
        );
        self::assertEquals(
            $customer->getLastname(),
            $response['customer']['orders']['items'][0]['returns']['items'][0]['customer']['lastname']
        );
        self::assertEquals(
            $customer->getEmail(),
            $response['customer']['orders']['items'][0]['returns']['items'][0]['customer']['email']
        );
        self::assertEquals(
            $this->idEncoder->encode((string)$rma->getEntityId()),
            $response['customer']['orders']['items'][0]['returns']['items'][0]['uid']
        );
        self::assertEquals(
            $currentPage,
            $response['customer']['orders']['items'][0]['returns']['page_info']['current_page']
        );
        self::assertEquals(
            $pageSize,
            $response['customer']['orders']['items'][0]['returns']['page_info']['page_size']
        );
        self::assertEquals(
            1,
            $response['customer']['orders']['items'][0]['returns']['page_info']['total_pages']
        );
        self::assertEquals(
            1,
            $response['customer']['orders']['items'][0]['returns']['total_count']
        );
    }

    /**
     * Test customer order returns with negative page size value
     *
     * @magentoConfigFixture default_store sales/magento_rma/enabled 1
     * @magentoApiDataFixture Magento/Rma/_files/rma_with_customer.php
     */
    public function testCustomerOrdersWithReturnsQueryWithNegativePageSize()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('pageSize value must be greater than 0.');

        $query = <<<QUERY
{
  customer {
    orders(filter: {number: {eq: "100000555"}}) {
      items {
        returns(pageSize: -1, currentPage: 1) {
          items {
            uid
            created_at
            customer{firstname lastname email}
            status
            number
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
     * Test customer order returns with zero page size value
     *
     * @magentoConfigFixture default_store sales/magento_rma/enabled 1
     * @magentoApiDataFixture Magento/Rma/_files/rma_with_customer.php
     */
    public function testCustomerOrdersWithReturnsQueryWithZeroPageSize()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('pageSize value must be greater than 0.');

        $query = <<<QUERY
{
  customer {
    orders(filter: {number: {eq: "100000555"}}) {
      items {
        returns(pageSize: 0, currentPage: 1) {
          items {
            uid
            created_at
            customer{firstname lastname email}
            status
            number
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
     * Test customer order returns with zero current page value
     *
     * @magentoConfigFixture default_store sales/magento_rma/enabled 1
     * @magentoApiDataFixture Magento/Rma/_files/rma_with_customer.php
     */
    public function testCustomerOrdersWithReturnsQueryWithZeroCurrentPage()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('currentPage value must be greater than 0.');

        $query = <<<QUERY
{
  customer {
    orders(filter: {number: {eq: "100000555"}}) {
      items {
        returns(pageSize: 10, currentPage: 0) {
          items {
            uid
            created_at
            customer{firstname lastname email}
            status
            number
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
     * Test customer order returns with negative current page value
     *
     * @magentoConfigFixture default_store sales/magento_rma/enabled 1
     * @magentoApiDataFixture Magento/Rma/_files/rma_with_customer.php
     */
    public function testCustomerOrdersWithReturnsQueryWithNegativeCurrentPage()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('currentPage value must be greater than 0.');

        $query = <<<QUERY
{
  customer {
    orders(filter: {number: {eq: "100000555"}}) {
      items {
        returns(pageSize: 10, currentPage: -1) {
          items {
            uid
            created_at
            customer{firstname lastname email}
            status
            number
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
    public function testCustomerOrdersWithReturnsQueryWithUnauthorizedCustomer()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The current customer isn\'t authorized.');

        $query = <<<QUERY
{
  customer {
    orders(filter: {number: {eq: "100000555"}}) {
      items {
        returns(pageSize: 10, currentPage: 1) {
          items {
            uid
            created_at
            customer{firstname lastname email}
            status
            number
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
  }
}
QUERY;

        $this->graphQlQuery($query);
    }

    /**
     * Test customer order returns without params
     *
     * @magentoApiDataFixture Magento/Rma/_files/rma_with_customer.php
     * @magentoConfigFixture sales/magento_rma/enabled 1
     * @magentoConfigFixture sales/magento_rma/use_store_address 1
     * @magentoConfigFixture shipping/origin/name test
     * @magentoConfigFixture shipping/origin/phone +380003434343
     * @magentoConfigFixture shipping/origin/street_line1 street
     * @magentoConfigFixture shipping/origin/street_line2 1
     * @magentoConfigFixture shipping/origin/city Montgomery
     * @magentoConfigFixture shipping/origin/region_id 1
     * @magentoConfigFixture shipping/origin/postcode 12345
     * @magentoConfigFixture shipping/origin/country_id US
     */
    public function testCustomerOrdersWithReturnsQueryWithoutParams()
    {
        $customerEmail = 'customer_uk_address@test.com';
        $orderIncrementId = '100000555';

        $query = <<<QUERY
{
  customer {
    orders(filter: {number: {eq: "{$orderIncrementId}"}}) {
      items {
        returns {
          items {
            uid
            created_at
            customer{firstname lastname email}
            status
            number
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
        $rma = $this->getCustomerReturnByOrder($customerEmail, $orderIncrementId);

        self::assertEquals(
            $rma->getDateRequested(),
            $response['customer']['orders']['items'][0]['returns']['items'][0]['created_at']
        );
        self::assertEquals(
            $rma->getIncrementId(),
            $response['customer']['orders']['items'][0]['returns']['items'][0]['number']
        );
        self::assertEqualsIgnoringCase(
            Status::STATE_APPROVED,
            $response['customer']['orders']['items'][0]['returns']['items'][0]['status']
        );
        self::assertEquals(
            $customer->getFirstname(),
            $response['customer']['orders']['items'][0]['returns']['items'][0]['customer']['firstname']
        );
        self::assertEquals(
            $customer->getLastname(),
            $response['customer']['orders']['items'][0]['returns']['items'][0]['customer']['lastname']
        );
        self::assertEquals(
            $customer->getEmail(),
            $response['customer']['orders']['items'][0]['returns']['items'][0]['customer']['email']
        );
        self::assertEquals(
            $this->idEncoder->encode((string)$rma->getEntityId()),
            $response['customer']['orders']['items'][0]['returns']['items'][0]['uid']
        );
        self::assertEquals(
            1,
            $response['customer']['orders']['items'][0]['returns']['page_info']['current_page']
        );
        self::assertEquals(
            20,
            $response['customer']['orders']['items'][0]['returns']['page_info']['page_size']
        );
        self::assertEquals(
            1,
            $response['customer']['orders']['items'][0]['returns']['page_info']['total_pages']
        );
        self::assertEquals(
            1,
            $response['customer']['orders']['items'][0]['returns']['total_count']
        );
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
    orders(filter: {number: {eq: "100000555"}}) {
      items {
        returns(pageSize: 10, currentPage: 1) {
          items {
            uid
            created_at
            customer{firstname lastname email}
            status
            number
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
     * Get customer return by order
     *
     * @param string $customerEmail
     * @param string $incrementId
     * @return RmaInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getCustomerReturnByOrder(string $customerEmail, string $incrementId): RmaInterface
    {
        $order = $this->orderFactory->create()->loadByIncrementId($incrementId);
        $customer = $this->customerRepository->get($customerEmail);

        $this->searchCriteriaBuilder->addFilter(Rma::CUSTOMER_ID, $customer->getId());
        $this->searchCriteriaBuilder->addFilter(Rma::ORDER_ID, $order->getEntityId());
        $searchResults = $this->rmaRepository->getList($this->searchCriteriaBuilder->create());

        return $searchResults->getFirstItem();
    }
}
