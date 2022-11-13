<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\CustomerBalance;

use Magento\Framework\ObjectManagerInterface;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\Quote\Model\QuoteIdMask;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResource;
use Magento\Framework\Registry;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test storeCredit balance query
 */
class StoreCreditBalanceQueryTest extends GraphQlAbstract
{
    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var QuoteCollectionFactory
     */
    private $quoteCollectionFactory;

    /**
     * @var CustomerTokenServiceInterface
     */
    private $customerTokenService;

    /**
     * @var QuoteResource
     */
    private $quoteResource;

    /**
     * @var QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;

    /**
     * @var CollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->registry =  $this->objectManager->get(Registry::class);
        $this->customerTokenService =  $this->objectManager->get(CustomerTokenServiceInterface::class);
        $this->quoteCollectionFactory =  $this->objectManager->get(QuoteCollectionFactory::class);
        $this->quoteResource =  $this->objectManager->get(QuoteResource::class);
        $this->quoteIdMaskFactory =  $this->objectManager->get(QuoteIdMaskFactory::class);
        $this->orderCollectionFactory =  $this->objectManager->get(CollectionFactory::class);
        $this->orderRepository =  $this->objectManager->get(OrderRepositoryInterface::class);
    }

    /**
     * Tests store credit balance and balance history after cancelling an order placed using store credit
     *
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/CustomerBalance/_files/customer_balance_default_website.php
     * @magentoApiDataFixture Magento/GiftCardAccount/_files/giftcardaccount.php
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testStoreCreditBalanceAndBalanceHistory()
    {
        $quantity = 3;
        $sku = 'simple_product';
        $giftCardCode = "giftcardaccount_fixture";

        $cartId = $this->createEmptyCart();

        $this->addProductToCart($cartId, $quantity, $sku);

        $this->setBillingAddress($cartId);
        $shippingMethod = $this->setShippingAddress($cartId);

        $this->setShippingMethod($cartId, $shippingMethod);

        $this->getRedeemGiftCardQuery($giftCardCode);
        $this->applyStoreCreditFromRedeemedGiftCard($cartId);
        $paymentMethodCode = 'free';
        $this->setPaymentMethod($cartId, $paymentMethodCode);
        $query = <<<QUERY
mutation {
  placeOrder(
    input: {
      cart_id: "{$cartId}"
    }
  ) {
    order {
      order_id
    }
  }
}
QUERY;
        $response = $this->graphQlMutation($query, [], '', $this->getHeaderMap());
        $orderId = $response['placeOrder']['order']['order_id'];
        /** @var Order $order */
        $order = $this->objectManager->get(Order::class);
        $order->loadByIncrementId($orderId);
        $order->cancel();

        $this->getCustomerStoreCreditBalanceQuery();
    }

    /**
     * Checks store credit balance when balance history in the configuration is disabled
     *
     * @magentoApiDataFixture Magento/CustomerBalance/_files/customer_balance_default_website.php
     * @magentoApiDataFixture Magento/CustomerBalance/_files/disable_customer_balance_show_history.php
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testDisabledStoreCreditBalanceHistory()
    {
        $query = <<<QUERY
{
 customer {
   store_credit {
    enabled
     current_balance {
       currency
       value
     }
     balance_history(pageSize:3, currentPage: 1) {
       items {
         action
         balance_change {    
           currency
           value
         }
         actual_balance {
           currency
           value
         }
       }
     }
    }
  }
 }
QUERY;
        $response = $this->graphQlMutation($query, [], '', $this->getHeaderMap());
        $this->assertTrue($response['customer']['store_credit']['enabled']);
        $this->assertNull($response['customer']['store_credit']['balance_history']);
        $this->assertEquals(
            50,
            $response['customer']['store_credit']['current_balance']['value']
        );
    }
    /**
     * Checks store credit balance when balance history in the configuration is disabled
     *
     * @magentoApiDataFixture Magento/CustomerBalance/_files/customer_balance_default_website.php
     * @magentoApiDataFixture Magento/CustomerBalance/_files/disable_customer_balance.php
     * @magentoApiDataFixture Magento/CustomerBalance/_files/disable_customer_balance_show_history.php
     */
    public function testForDisabledStoreCreditBalanceAndBalanceHistory()
    {
        $query = <<<QUERY
{
 customer {
   store_credit {
    enabled
     current_balance {
       currency
       value
     }
     balance_history(pageSize:3, currentPage: 1) {
       items {
         action
         balance_change {    
           currency
           value
         }
         actual_balance {
           currency
           value
         }
       }
     }
    }
  }
 }
QUERY;
        $response = $this->graphQlMutation($query, [], '', $this->getHeaderMap());
        $this->assertFalse($response['customer']['store_credit']['enabled']);
        $this->assertNull($response['customer']['store_credit']['balance_history']);
    }

    /**
     * Checks for store credit balance and the store credit balance history
     *
     * @return void
     */
    private function getCustomerStoreCreditBalanceQuery() : void
    {
        $query = <<<QUERY
{
 customer {
   store_credit {
    enabled
     current_balance {
       currency
       value
     }
     balance_history(pageSize:3, currentPage: 1) {
       items {
         action
         balance_change {    
           currency
           value
         }
         actual_balance {
           currency
           value
         }
       }
     }
    }
  }
 }
QUERY;
        $response = $this->graphQlMutation($query, [], '', $this->getHeaderMap());
        $this->assertArrayHasKey('store_credit', $response['customer']);
        $this->assertTrue($response['customer']['store_credit']['enabled']);
        $this->assertArrayHasKey('current_balance', $response['customer']['store_credit']);
        $this->assertNotNull($response['customer']['store_credit']['current_balance']);
        $this->assertArrayHasKey('balance_history', $response['customer']['store_credit']);
        $balanceHistory = $response['customer']['store_credit']['balance_history'];
        $this->assertNotNull($balanceHistory);
        $balanceHistoryItems = $balanceHistory['items'];

        $this->assertEquals(
            $balanceHistoryItems[0]['actual_balance']['value'],
            $response['customer']['store_credit']['current_balance']['value']
        );
        $expectedBalanceHistoryActions = ['Reverted' , 'Used', 'Updated', 'Created'];
        $expectedBalanceChangeValues = [45, -45, 9.99, 50];
        $expectedActualBalanceValues = [59.99, 14.99, 59.99, 50.00];
        foreach ($balanceHistoryItems as $itemKey => $itemData) {
            $this->assertNotEmpty($itemData);
            $this->assertEquals($expectedBalanceHistoryActions[$itemKey], $balanceHistoryItems[$itemKey]['action']);
            $this->assertEquals(
                $expectedBalanceChangeValues[$itemKey],
                $balanceHistoryItems[$itemKey]['balance_change']['value']
            );
            $this->assertEquals(
                $expectedActualBalanceValues[$itemKey],
                $balanceHistoryItems[$itemKey]['actual_balance']['value']
            );
        }
    }

    /**
     * @return string
     */
    private function createEmptyCart(): string
    {
        $query = <<<QUERY
mutation {
  createEmptyCart
}
QUERY;
        $response = $this->graphQlMutation($query, [], '', $this->getHeaderMap());
        $this->assertArrayHasKey('createEmptyCart', $response);
        $this->assertNotEmpty($response['createEmptyCart']);

        return $response['createEmptyCart'];
    }

    /**
     * @param string $cartId
     * @param float $quantity
     * @param string $sku
     * @return void
     */
    private function addProductToCart(string $cartId, float $quantity, string $sku): void
    {
        $query = <<<QUERY
mutation {  
  addSimpleProductsToCart(
    input: {
      cart_id: "{$cartId}"
      cart_items: [
        {
          data: {
            quantity: {$quantity}
            sku: "{$sku}"
          }
        }
      ]
    }
  ) {
    cart {
      items {
        quantity
        product {
          sku
        }
      }
    }
  }
}
QUERY;
        $this->graphQlMutation($query, [], '', $this->getHeaderMap());
    }

    /**
     * @param string $cartId
     * @param array $auth
     * @return array
     */
    private function setBillingAddress(string $cartId): void
    {
        $query = <<<QUERY
mutation {
  setBillingAddressOnCart(
    input: {
      cart_id: "{$cartId}"
      billing_address: {
         address: {
          firstname: "test firstname"
          lastname: "test lastname"
          company: "test company"
          street: ["test street 1", "test street 2"]
          city: "test city"
          postcode: "887766"
          telephone: "88776655"
          region: "TX"
          country_code: "US"
          save_in_address_book: false
         }
      }
    }
  ) {
    cart {
      billing_address {
        __typename
      }
    }
  }
}
QUERY;
        $this->graphQlMutation($query, [], '', $this->getHeaderMap());
    }

    /**
     * @param string $cartId
     * @return array
     */
    private function setShippingAddress(string $cartId): array
    {
        $query = <<<QUERY
mutation {
  setShippingAddressesOnCart(
    input: {
      cart_id: "$cartId"
      shipping_addresses: [
        {
          address: {
            firstname: "test firstname"
            lastname: "test lastname"
            company: "test company"
            street: ["test street 1", "test street 2"]
            city: "test city"
            region: "TX"
            postcode: "887766"
            country_code: "US"
            telephone: "88776655"
            save_in_address_book: false
          }
        }
      ]
    }
  ) {
    cart {
      shipping_addresses {
        available_shipping_methods {
          carrier_code
          method_code
          amount {
            value
          }
        }
      }
    }
  }
}
QUERY;
        $response = $this->graphQlMutation($query, [], '', $this->getHeaderMap());
        $this->assertArrayHasKey('setShippingAddressesOnCart', $response);
        $this->assertCount(1, $response['setShippingAddressesOnCart']['cart']['shipping_addresses']);

        $shippingAddress = current($response['setShippingAddressesOnCart']['cart']['shipping_addresses']);
        $this->assertCount(1, $shippingAddress['available_shipping_methods']);

        $availableShippingMethod = current($shippingAddress['available_shipping_methods']);
        $this->assertArrayHasKey('carrier_code', $availableShippingMethod);

        return $availableShippingMethod;
    }

    /**
     * @param string $cartId
     * @param array $method
     * @return array
     */
    private function setShippingMethod(string $cartId, array $method): array
    {
        $query = <<<QUERY
mutation {
  setShippingMethodsOnCart(input:  {
    cart_id: "{$cartId}", 
    shipping_methods: [
      {
         carrier_code: "{$method['carrier_code']}"
         method_code: "{$method['method_code']}"
      }
    ]
  }) {
    cart {
      available_payment_methods {
        code
        title
      }
    }
  }
}
QUERY;
        $response = $this->graphQlMutation($query, [], '', $this->getHeaderMap());
        $availablePaymentMethod = current(
            $response['setShippingMethodsOnCart']
            ['cart']['available_payment_methods']
        );
        return $availablePaymentMethod;
    }

    /**
     * @param string $cartId
     * @param string $method
     * @return void
     */
    private function setPaymentMethod(string $cartId, string $method): void
    {
        $query = <<<QUERY
mutation {
  setPaymentMethodOnCart(
    input: {
      cart_id: "{$cartId}"
      payment_method: {
        code: "{$method}"
      }
    }
  ) {
    cart {
      selected_payment_method {
        code
      }
    }
  }
}
QUERY;
        $this->graphQlMutation($query, [], '', $this->getHeaderMap());
    }

    /**
     * @param $cartId
     */
    private function applyStoreCreditFromRedeemedGiftCard($cartId) : void
    {
        $query = <<<QUERY
mutation
{
  applyStoreCreditToCart(input:{cart_id:"$cartId"})
  {
    cart{
      applied_store_credit{
        applied_balance
        {
          currency
          value
        }
        current_balance{
          currency
          value
        }
      }
      
    }
    
  }
}
QUERY;
        $response = $this->graphQlMutation($query, [], '', $this->getHeaderMap());
        $this->assertArrayHasKey('applyStoreCreditToCart', $response);
        $appliedStoreCredit = $response['applyStoreCreditToCart']['cart']['applied_store_credit'];

        $this->assertEquals('USD', $appliedStoreCredit['applied_balance']['currency']);
        $this->assertEquals(45.00, $appliedStoreCredit['applied_balance']['value']);

        $this->assertEquals('USD', $appliedStoreCredit['current_balance']['currency']);
        $this->assertEquals(59.99, $appliedStoreCredit['current_balance']['value']);
    }

    /**
     * Get redeemGiftCardBalanceAsStoreCredit query string
     *
     * @param string $giftCardCode
     */
    private function getRedeemGiftCardQuery(string $giftCardCode)
    {
        $redeemGiftCardQuery =  <<<QUERY
mutation{
  redeemGiftCardBalanceAsStoreCredit(input: {gift_card_code: "{$giftCardCode}"})
  {
    code
    balance{
      value
      currency
    }
  }
}
QUERY;
        $result = $this->graphQlMutation($redeemGiftCardQuery, [], '', $this->getHeaderMap());
        $this->assertArrayNotHasKey('errors', $result);
    }

    /**
     * @param string $username
     * @param string $password
     * @return array
     */
    private function getHeaderMap(string $username = 'customer@example.com', string $password = 'password'): array
    {
        $customerToken = $this->customerTokenService->createCustomerAccessToken($username, $password);

        $headerMap = ['Authorization' => 'Bearer ' . $customerToken];
        return $headerMap;
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        $this->deleteQuote();
        $this->deleteOrder();
        parent::tearDown();
    }

    /**
     * @return void
     */
    private function deleteQuote(): void
    {
        $quoteCollection = $this->quoteCollectionFactory->create();
        foreach ($quoteCollection as $quote) {
            $this->quoteResource->delete($quote);
            /** @var QuoteIdMask $quoteIdMask */
            $quoteIdMask = $this->quoteIdMaskFactory->create();
            $quoteIdMask->setQuoteId($quote->getId())
                ->delete();
        }
    }

    /**
     * @return void
     */
    private function deleteOrder() : void
    {
        $this->registry->unregister('isSecureArea');
        $this->registry->register('isSecureArea', true);

        $orderCollection = $this->orderCollectionFactory->create();
        foreach ($orderCollection as $order) {
            $this->orderRepository->delete($order);
        }
        $this->registry->unregister('isSecureArea');
        $this->registry->register('isSecureArea', false);
    }
}
