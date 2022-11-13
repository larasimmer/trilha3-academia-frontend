<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\GraphQl\Rma;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\ObjectManagerInterface;
use Magento\GraphQl\GetCustomerAuthenticationHeader;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test for eligible for return flag
 */
class EligibleForReturnFlagTest extends GraphQlAbstract
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
     * @var GetCustomerAuthenticationHeader
     */
    private $getCustomerAuthenticationHeader;

    /**
     * @var OrderInterfaceFactory
     */
    private $orderFactory;

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
        $this->getCustomerAuthenticationHeader = $this->objectManager->get(GetCustomerAuthenticationHeader::class);
        $this->orderFactory = $this->objectManager->get(OrderInterfaceFactory::class);
        $this->idEncoder = $this->objectManager->get(Uid::class);
    }

    /**
     * Test eligible items for return
     *
     * @magentoApiDataFixture Magento/Sales/_files/customer_order_with_two_items.php
     * @magentoConfigFixture default_store sales/magento_rma/enabled 1
     * @magentoConfigFixture default_store sales/magento_rma/enabled_on_product 1
     */
    public function testEligibleForReturn()
    {
        $orderNumber = '100000555';

        $query = <<<QUERY
{
  customer {
    orders(filter: {number: {eq: "{$orderNumber}"}}) {
      items {
        items {
          id
          eligible_for_return
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
            $this->getCustomerAuthenticationHeader->execute('customer_uk_address@test.com', 'password')
        );

        $order = $this->orderFactory->create()->loadByIncrementId($orderNumber);
        $orderItems = $order->getItems();

        $expectedResult = [];

        foreach ($orderItems as $item) {
            $expectedResult[] = [
                'id' => $this->idEncoder->encode($item->getItemId()),
                'eligible_for_return' => true
            ];
        }

        self::assertEqualsCanonicalizing(
            $expectedResult,
            $response['customer']['orders']['items'][0]['items']
        );
    }

    /**
     * Test eligible items for return
     *
     * @magentoConfigFixture default_store sales/magento_rma/enabled 0
     * @magentoConfigFixture default_store sales/magento_rma/enabled_on_product 0
     * @magentoApiDataFixture Magento/Sales/_files/customer_order_with_two_items.php
     */
    public function testWithDisabledRma()
    {
        $orderNumber = '100000555';

        $query = <<<QUERY
{
  customer {
    orders(filter: {number: {eq: "{$orderNumber}"}}) {
      items {
        items {
          id
          eligible_for_return
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
            $this->getCustomerAuthenticationHeader->execute('customer_uk_address@test.com', 'password')
        );

        $order = $this->orderFactory->create()->loadByIncrementId($orderNumber);
        $orderItems = $order->getItems();

        $expectedResult = [];

        foreach ($orderItems as $item) {
            $expectedResult[] = [
                'id' => $this->idEncoder->encode($item->getItemId()),
                'eligible_for_return' => false
            ];
        }

        self::assertEqualsCanonicalizing(
            $expectedResult,
            $response['customer']['orders']['items'][0]['items']
        );
    }

    /**
     * Test eligible items for return with not existing order number
     *
     * @magentoConfigFixture default_store sales/magento_rma/enabled 1
     * @magentoConfigFixture default_store sales/magento_rma/enabled_on_product 1
     * @magentoApiDataFixture Magento/Sales/_files/customer_order_with_two_items.php
     */
    public function testWithNotExistingOrderNumber()
    {
        $query = <<<QUERY
{
  customer {
    orders(filter: {number: {eq: "111000555"}}) {
      items {
        items {
          id
          eligible_for_return
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
            $this->getCustomerAuthenticationHeader->execute('customer_uk_address@test.com', 'password')
        );

        self::assertEmpty($response['customer']['orders']['items']);
    }

    /**
     * Test eligible items for return with unauthorized customer
     *
     * @magentoConfigFixture default_store sales/magento_rma/enabled 1
     * @magentoConfigFixture default_store sales/magento_rma/enabled_on_product 1
     * @magentoApiDataFixture Magento/Sales/_files/customer_order_with_two_items.php
     */
    public function testUnauthorized()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The current customer isn\'t authorized.');

        $query = <<<QUERY
{
  customer {
    orders(filter: {number: {eq: "100000555"}}) {
      items {
        items {
          id
          eligible_for_return
        }
      }
    }
  }
}
QUERY;
        $this->graphQlQuery($query);
    }
}
