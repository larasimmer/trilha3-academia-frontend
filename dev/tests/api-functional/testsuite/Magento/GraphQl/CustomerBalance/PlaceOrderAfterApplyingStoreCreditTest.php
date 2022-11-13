<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\CustomerBalance;

use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\Quote\Model\QuoteIdMask;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResource;
use Magento\Framework\Registry;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Place an order after Store credit is applied on cart
 */
class PlaceOrderAfterApplyingStoreCreditTest extends GraphQlAbstract
{
    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var QuoteCollectionFactory
     */
    private $quoteCollectionFactory;

    /** @var  CustomerTokenServiceInterface */
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
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->registry = $objectManager->get(Registry::class);
        $this->customerTokenService = $objectManager->get(CustomerTokenServiceInterface::class);
        $this->quoteCollectionFactory = $objectManager->get(QuoteCollectionFactory::class);
        $this->quoteResource = $objectManager->get(QuoteResource::class);
        $this->quoteIdMaskFactory = $objectManager->get(QuoteIdMaskFactory::class);
        $this->orderCollectionFactory = $objectManager->get(CollectionFactory::class);
        $this->orderRepository = $objectManager->get(OrderRepositoryInterface::class);
    }

    /**
     *  Store credit balance pays the entire cart total
     *
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/CustomerBalance/_files/customer_balance_default_website.php
     */
    public function testPlaceOrderWithStoreCreditCompletelyPaysCartTotal()
    {
        $quantity = 3;

        $sku = 'simple_product';
        $cartId = $this->createEmptyCart();

        $this->addProductToCart($cartId, $quantity, $sku);

        $this->setBillingAddress($cartId);
        $shippingMethod = $this->setShippingAddress($cartId);

        $this->setShippingMethod($cartId, $shippingMethod);
        $this->applyStoreCreditToCart($cartId);
        $paymentMethodCode = 'free';
        $this->setPaymentMethod($cartId, $paymentMethodCode);
        $this->placeOrder($cartId);
    }

    /**
     *  Partial cart total is paid by store credit and the rest paid by another available payment method
     *
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/CustomerBalance/_files/customer_balance_default_website.php
     */
    public function testPlaceOrderWithStoreCreditPartiallyPaysCartTotal()
    {
        $quantity = 4;

        $sku = 'simple_product';
        $cartId = $this->createEmptyCart();
        $this->addProductToCart($cartId, $quantity, $sku);

        $this->setBillingAddress($cartId);
        $shippingMethod = $this->setShippingAddress($cartId);

        $this->setShippingMethod($cartId, $shippingMethod);
        $this->applyStoreCreditToCart($cartId);
        $paymentMethodCode = 'checkmo';
        $this->setPaymentMethod($cartId, $paymentMethodCode);

        $this->placeOrder($cartId);
    }

    /**
     * Use case where Redeeming a gift card as store credit which in turn used to complete the checkout
     *
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/CustomerBalance/_files/customer_balance_default_website.php
     * @magentoApiDataFixture Magento/GiftCardAccount/_files/giftcardaccount.php
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCompleteCheckOutWithStoreCreditAfterRedeemingGiftCard()
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
        $this->placeOrder($cartId);
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
        self::assertArrayHasKey('createEmptyCart', $response);
        self::assertNotEmpty($response['createEmptyCart']);

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
        self::assertArrayHasKey('setShippingAddressesOnCart', $response);
        self::assertCount(1, $response['setShippingAddressesOnCart']['cart']['shipping_addresses']);

        $shippingAddress = current($response['setShippingAddressesOnCart']['cart']['shipping_addresses']);
        self::assertCount(1, $shippingAddress['available_shipping_methods']);

        $availableShippingMethod = current($shippingAddress['available_shipping_methods']);
        self::assertArrayHasKey('carrier_code', $availableShippingMethod);
        self::assertNotEmpty($availableShippingMethod['carrier_code']);
        self::assertNotEmpty($availableShippingMethod['method_code']);
        self::assertNotEmpty($availableShippingMethod['amount']['value']);

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
        self::assertArrayHasKey('setShippingMethodsOnCart', $response);
        self::assertArrayHasKey('cart', $response['setShippingMethodsOnCart']);
        self::assertArrayHasKey('available_payment_methods', $response['setShippingMethodsOnCart']['cart']);

        $availablePaymentMethod = current(
            $response['setShippingMethodsOnCart']
              ['cart']['available_payment_methods']
        );
        self::assertNotEmpty($availablePaymentMethod['code']);
        self::assertNotEmpty($availablePaymentMethod['title']);

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
    private function applyStoreCreditToCart($cartId) : void
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
        self::assertNotNull($appliedStoreCredit);
        self::assertNotEmpty($appliedStoreCredit['applied_balance'], "Failed: 'applied_balance' must not be empty");
        self::assertEquals('USD', $appliedStoreCredit['applied_balance']['currency']);
        self::assertEquals(45.00, $appliedStoreCredit['applied_balance']['value']);

        self::assertEquals('USD', $appliedStoreCredit['current_balance']['currency']);
        self::assertEquals(59.99, $appliedStoreCredit['current_balance']['value']);
    }

    /**
     * @param string $cartId
     * @return void
     */
    private function placeOrder(string $cartId): void
    {
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
        self::assertArrayHasKey('placeOrder', $response);
        self::assertArrayHasKey('order', $response['placeOrder']);
        self::assertArrayHasKey('order_id', $response['placeOrder']['order']);
        self::assertNotEmpty($response['placeOrder']['order']['order_id']);
    }

    /**
     * Get redeemGiftCardBalanceAsStoreCredit query string
     *
     * @param string $giftCardCode
     * @return string
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
        $this->assertArrayHasKey('redeemGiftCardBalanceAsStoreCredit', $result);
        $resultData = $result['redeemGiftCardBalanceAsStoreCredit'];
        $this->assertEquals($giftCardCode, $resultData['code']);
        $this->assertEquals(0, $resultData['balance']['value']);
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
