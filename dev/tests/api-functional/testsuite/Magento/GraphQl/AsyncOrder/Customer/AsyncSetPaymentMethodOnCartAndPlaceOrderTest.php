<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\AsyncOrder\Customer;

use Exception;
use Magento\Framework\Registry;
use Magento\GraphQl\Quote\GetMaskedQuoteIdByReservedOrderId;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\OfflinePayments\Model\Checkmo;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test for asynchronous checkout by registered customer
 */
class AsyncSetPaymentMethodOnCartAndPlaceOrderTest extends GraphQlAbstract
{
    /**
     * @var CustomerTokenServiceInterface
     */
    private $customerTokenService;

    /**
     * @var GetMaskedQuoteIdByReservedOrderId
     */
    private $getMaskedQuoteIdByReservedOrderId;

    /**
     * @var CollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->getMaskedQuoteIdByReservedOrderId = $objectManager->get(GetMaskedQuoteIdByReservedOrderId::class);
        $this->customerTokenService = $objectManager->get(CustomerTokenServiceInterface::class);
        $this->orderCollectionFactory = $objectManager->get(CollectionFactory::class);
        $this->orderRepository = $objectManager->get(OrderRepositoryInterface::class);
        $this->registry = Bootstrap::getObjectManager()->get(Registry::class);
    }

    /**
     * Tests asynchronous setPaymentOndPlaceOrder joint operation with simple product
     *
     * @magentoApiDataFixture Magento/AsyncOrder/_files/async_mode.php
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoConfigFixture default_store carriers/flatrate/active 1
     * @magentoConfigFixture default_store payment/checkmo/active 1
     * @magentoConfigFixture default_store payment/cashondelivery/active 1
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/customer/create_empty_cart.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/set_new_shipping_address.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/set_new_billing_address.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/set_flatrate_shipping_method.php
     */
    public function testAsyncSetPaymentMethodAndPlaceOrder()
    {
        $reservedOrderId = 'test_quote';
        $methodCode ='cashondelivery';
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute($reservedOrderId);

        $query = $this->setPaymentMethodAndPlaceOrderQuery($maskedQuoteId, $methodCode);
        $response = $this->graphQlMutation($query, [], '', $this->getHeaderMap());
        $this->assertArrayHasKey('setPaymentMethodAndPlaceOrder', $response);
        $this->assertArrayHasKey('order_number', $response['setPaymentMethodAndPlaceOrder']['order']);
        $orderNumber = $response['setPaymentMethodAndPlaceOrder']['order']['order_number'];
        $customerOrderResponse = $this->getCustomerOrderQuery($orderNumber);
        $orderStatus = $customerOrderResponse['customer']['orders']['items'][0]['status'];
        //check the order status before the placeOrder consumer is run
        $this->assertEquals('Received', $orderStatus);
        // run the consumer
        $this->runPlaceOrderProcessorConsumer();
        // query the  customer order after consumer is run
        $customerOrderResponse = $this->getCustomerOrderQuery($orderNumber);
        $orderStatus = $customerOrderResponse['customer']['orders']['items'][0]['status'];
        $this->assertEquals('Pending', $orderStatus);
        $this->assertBillingAddressFields($customerOrderResponse['customer']['orders']['items'][0]['billing_address']);
        $this->assertShippingAddressFields(
            $customerOrderResponse['customer']['orders']['items'][0]['shipping_address']
        );
        $this->assertEquals(
            'cashondelivery',
            $customerOrderResponse['customer']['orders']['items'][0]['payment_methods'][0]['type']
        );
    }

    /**
     *  Tests asynchronous setPaymentOndPlaceOrder joint operation with virtual product
     *
     * @magentoApiDataFixture Magento/AsyncOrder/_files/async_mode.php
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/Catalog/_files/product_virtual.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/customer/create_empty_cart.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/add_virtual_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/set_new_billing_address.php
     */
    public function testAsyncSetPaymentMethodAndPlaceOrderWithVirtualProduct()
    {
        $reservedOrderId = 'test_quote';
        $methodCode = Checkmo::PAYMENT_METHOD_CHECKMO_CODE;
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute($reservedOrderId);

        $query = $this->setPaymentMethodAndPlaceOrderQuery($maskedQuoteId, $methodCode);
        $response = $this->graphQlMutation($query, [], '', $this->getHeaderMap());
        $this->assertArrayHasKey('setPaymentMethodAndPlaceOrder', $response);
        $this->assertArrayHasKey('order_number', $response['setPaymentMethodAndPlaceOrder']['order']);
        $orderNumber = $response['setPaymentMethodAndPlaceOrder']['order']['order_number'];
        $customerOrderResponse = $this->getCustomerOrderQuery($orderNumber);
        $orderStatus = $customerOrderResponse['customer']['orders']['items'][0]['status'];
        //check the order status before the placeOrder consumer is run
        $this->assertEquals('Received', $orderStatus);
        // run the consumer
        $this->runPlaceOrderProcessorConsumer();
        // query the  customer order after consumer is run
        $customerOrderResponse = $this->getCustomerOrderQuery($orderNumber);
        $orderStatus = $customerOrderResponse['customer']['orders']['items'][0]['status'];
        $this->assertEquals('Pending', $orderStatus);
    }

    /**
     *  Verifies that exception is returned if no shipping address is set
     *
     * @magentoApiDataFixture Magento/AsyncOrder/_files/async_mode.php
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/customer/create_empty_cart.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     */
    public function testAsyncSetPaymentMethodAndPlaceOrderWithNoShippingAddress()
    {
        $reservedOrderId = 'test_quote';
        $methodCode = 'checkmo';
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute($reservedOrderId);
        $query = $this->setPaymentMethodAndPlaceOrderQuery($maskedQuoteId, $methodCode);

        $this->expectExceptionMessage(
            'The shipping address is missing. Set the address and try again.'
        );
        $this->graphQlMutation($query, [], '', $this->getHeaderMap());
    }

    /**
     * OVerifies that exception is returned if no shipping method is set
     *
     * @magentoApiDataFixture Magento/AsyncOrder/_files/async_mode.php
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/customer/create_empty_cart.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/set_new_shipping_address.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/set_new_billing_address.php
     */
    public function testAsyncSetPaymentMethodAndPlaceOrderWithNoShippingMethod()
    {
        $reservedOrderId = 'test_quote';
        $methodCode = 'checkmo';
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute($reservedOrderId);
        $query = $this->setPaymentMethodAndPlaceOrderQuery($maskedQuoteId, $methodCode);

        $this->expectExceptionMessage(
            'Unable to place order: The shipping method is missing. Select the shipping method and try again'
        );
        $this->graphQlMutation($query, [], '', $this->getHeaderMap());
    }

    /**
     *  Tests that exception is returned if billing address is missing on cart
     *
     * @magentoApiDataFixture Magento/AsyncOrder/_files/async_mode.php
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoConfigFixture default_store carriers/flatrate/active 1
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/customer/create_empty_cart.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/set_new_shipping_address.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/set_flatrate_shipping_method.php
     */
    public function testAsyncSetPaymentMethodAndPlaceOrderWithNoBillingAddress()
    {
        $reservedOrderId = 'test_quote';
        $methodCode = 'checkmo';
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute($reservedOrderId);
        $query = $this->setPaymentMethodAndPlaceOrderQuery($maskedQuoteId, $methodCode);

        $this->expectExceptionMessageMatches(
            '/Unable to place order: Please check the billing address information*/'
        );
        $this->graphQlMutation($query, [], '', $this->getHeaderMap());
    }

    /**
     * Tests if exception is returned for asynchronous setPaymentOndPlaceOrder mutation for OOS product
     *
     * @magentoApiDataFixture Magento/AsyncOrder/_files/async_mode.php
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoConfigFixture default_store carriers/flatrate/active 1
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/customer/create_empty_cart.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/set_new_shipping_address.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/set_new_billing_address.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/set_flatrate_shipping_method.php
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/set_simple_product_out_of_stock.php
     */
    public function testAsyncSetPaymentMethodAndPlaceOrderWithOutOfStockProduct()
    {
        $reservedOrderId = 'test_quote';
        $methodCode = 'checkmo';
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute($reservedOrderId);
        $query = $this->setPaymentMethodAndPlaceOrderQuery($maskedQuoteId, $methodCode);

        $this->expectExceptionMessage('Unable to place order: Some of the products are out of stock');
        $this->graphQlMutation($query, [], '', $this->getHeaderMap());
    }

    /**
     * @param string $maskedQuoteId
     * @return string
     */
    private function setPaymentMethodAndPlaceOrderQuery(string $maskedQuoteId, string $paymentMethod): string
    {
        return <<<QUERY
mutation{
  setPaymentMethodAndPlaceOrder(input: {
      cart_id: "$maskedQuoteId"
      payment_method: {
          code: "$paymentMethod"
      }
  }) {
    order {
      order_number
    }
  }
}
QUERY;
    }

    /**
     * @param string $username
     * @param string $password
     * @return array
     * @throws \Magento\Framework\Exception\AuthenticationException
     */
    private function getHeaderMap(string $username = 'customer@example.com', string $password = 'password'): array
    {
        $customerToken = $this->customerTokenService->createCustomerAccessToken($username, $password);
        $headerMap = ['Authorization' => 'Bearer ' . $customerToken];
        return $headerMap;
    }

    /**
     * Get customer order query
     *
     * @param string $orderNumber
     * @return array
     */
    private function getCustomerOrderQuery($orderNumber): array
    {
        $query =
            <<<QUERY
{
  customer
  {
   orders(filter:{number:{eq:"{$orderNumber}"}}) {
    total_count
    items
    {
      id
      number
      status
      order_date
      shipping_method
      payment_methods
      {
        name
        type
        additional_data
        {
         name
         value
         }
      }
      shipping_address {
         ... address
      }
      billing_address {
      ... address
      }
      items{
        quantity_ordered
        product_sku
        product_name
        product_sale_price{currency value}
      }
      total {
        base_grand_total {
          value
          currency
           }
        grand_total {
           value
           currency
           }
        subtotal {
           value
           currency
           }
        }
    }
   }
 }
}

fragment address on OrderAddress {
   firstname
   lastname
   city
   company
   country_code
   postcode
   street
   region
   telephone
   }
QUERY;
        $response = $this->graphQlQuery(
            $query,
            [],
            '',
            $this->getHeaderMap()
        );

        $this->assertArrayHasKey('orders', $response['customer']);
        $this->assertArrayHasKey('items', $response['customer']['orders']);
        return $response;
    }

    /**
     * Run the placeOrderProcessor consumer
     */
    private function runPlaceOrderProcessorConsumer()
    {
        $appDir = dirname(Bootstrap::getInstance()->getAppTempDir());
        $out = '';
        // phpcs:ignore Magento2.Security.InsecureFunction
        exec("php -f {$appDir}/bin/magento queue:consumers:start placeOrderProcessor --max-messages=1", $out);
        //wait until the queue message status changes to "COMPLETE"
        sleep(30);
    }

    /**
     *  Assert billing address fields
     *
     * @param $actualBillingResponse
     */
    private function assertBillingAddressFields($actualBillingAddressInResponse)
    {
        $assertionMap=[
            ['response_field' => 'firstname', 'expected_value' => 'John'],
            ['response_field' => 'lastname', 'expected_value' => 'Smith'],
            ['response_field' => 'city', 'expected_value' => 'CityM'],
            ['response_field' => 'company', 'expected_value' => 'CompanyName'],
            ['response_field' => 'country_code', 'expected_value' => 'US'],
            ['response_field' => 'postcode', 'expected_value' => '75477'],
            ['response_field' => 'street', 'expected_value' => [0 => 'Green str, 67']],
            ['response_field' => 'region', 'expected_value' => 'Alabama'],
            ['response_field' => 'telephone', 'expected_value' => '3468676']
        ];
        $this->assertResponseFields($actualBillingAddressInResponse, $assertionMap);
    }

    /**
     *  Assert billing address fields
     *
     * @param $actualBillingResponse
     */
    private function assertShippingAddressFields($actualShippingAdressInResponse)
    {
        $assertionMap=[
            ['response_field' => 'firstname', 'expected_value' => 'John'],
            ['response_field' => 'lastname', 'expected_value' => 'Smith'],
            ['response_field' => 'city', 'expected_value' => 'CityM'],
            ['response_field' => 'company', 'expected_value' => 'CompanyName'],
            ['response_field' => 'country_code', 'expected_value' => 'US'],
            ['response_field' => 'postcode', 'expected_value' => '75477'],
            ['response_field' => 'street', 'expected_value' => [0 => 'Green str, 67']],
            ['response_field' => 'region', 'expected_value' => 'Alabama'],
            ['response_field' => 'telephone', 'expected_value' => '3468676']
        ];
        $this->assertResponseFields($actualShippingAdressInResponse, $assertionMap);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        $this->registry->unregister('isSecureArea');
        $this->registry->register('isSecureArea', true);

        $orderCollection = $this->orderCollectionFactory->create();
        foreach ($orderCollection as $order) {
            $this->orderRepository->delete($order);
        }
        $this->registry->unregister('isSecureArea');
        $this->registry->register('isSecureArea', false);

        parent::tearDown();
    }
}
