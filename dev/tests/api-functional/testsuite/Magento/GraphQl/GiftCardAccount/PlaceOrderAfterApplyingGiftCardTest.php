<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\GiftCardAccount;

use Magento\Quote\Model\Quote;
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
 * End to end checkout test for guest after applying gift card on cart
 */
class PlaceOrderAfterApplyingGiftCardTest extends GraphQlAbstract
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
        parent::setUp();

        $objectManager = Bootstrap::getObjectManager();
        $this->registry = $objectManager->get(Registry::class);
        $this->quoteCollectionFactory = $objectManager->get(QuoteCollectionFactory::class);
        $this->quoteResource = $objectManager->get(QuoteResource::class);
        $this->quoteIdMaskFactory = $objectManager->get(QuoteIdMaskFactory::class);
        $this->orderCollectionFactory = $objectManager->get(CollectionFactory::class);
        $this->orderRepository = $objectManager->get(OrderRepositoryInterface::class);
    }

    /**
     *  Gift card pays for the total cart
     *
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/GiftCardAccount/_files/giftcardaccounts_for_search.php
     */
    public function testCheckOutWhenGiftCardCompleteleyPaysCartTotal()
    {
        $quantity = 3;

        $sku = 'simple_product';
        $giftCardCode ='gift_card_account_5';
        $cartId = $this->createEmptyCart();
        $this->setGuestEmailOnCart($cartId);
        $this->addProductToCart($cartId, $quantity, $sku);

        $this->setBillingAddress($cartId);
        $shippingMethod = $this->setShippingAddress($cartId, 'TX');

        $this->setShippingMethod($cartId, $shippingMethod);
        $this->applyGiftCardToCart($cartId, $giftCardCode);
        $paymentMethodCode = 'free';
        $this->setPaymentMethod($cartId, $paymentMethodCode);
        $this->placeOrder($cartId);
        $giftCardAccountQuery = $this->getGiftCardAccountQuery($giftCardCode);
        $result = $this->graphQlQuery($giftCardAccountQuery);
        // check the balance left on the card once the order is placed after gift card is applied
        $this->assertEquals(5, $result['giftCardAccount']['balance']['value']);
    }

    /**
     *  Gift card pays for the total cart
     *
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/GiftCardAccount/_files/giftcardaccounts_for_search.php
     * @magentoApiDataFixture Magento/GraphQl/Tax/_files/tax_rule_for_region_1.php
     * @magentoApiDataFixture Magento/GraphQl/Tax/_files/tax_calculation_shipping_excludeTax_order_display_settings.php
     */
    public function testCheckOutWhenGiftCardCompleteleyPaysCartZeroTotal()
    {
        $quantity = 3;

        $sku = 'simple_product';
        $giftCardCode ='gift_card_account_6';
        $cartId = $this->createEmptyCart();
        $this->setGuestEmailOnCart($cartId);
        $this->addProductToCart($cartId, $quantity, $sku);

        $this->setBillingAddress($cartId);
        $this->applyGiftCardToCart($cartId, $giftCardCode);
        $shippingMethod = $this->setShippingAddress($cartId, 'TX');
        $this->checkAvailablePaymentMethod($cartId, 'free', true);

        $this->setShippingMethod($cartId, $shippingMethod);

        $this->setShippingAddress($cartId, 'AL');
        $this->checkAvailablePaymentMethod($cartId, 'free', false);

        $this->setShippingAddress($cartId, 'TX');
        $paymentMethodCode = 'free';
        $this->setPaymentMethod($cartId, $paymentMethodCode);
        $this->placeOrder($cartId);
        $giftCardAccountQuery = $this->getGiftCardAccountQuery($giftCardCode);
        $result = $this->graphQlQuery($giftCardAccountQuery);
        // check the balance left on the card once the order is placed after gift card is applied
        $this->assertEquals(0, $result['giftCardAccount']['balance']['value']);
    }

    /**
     *  Partial cart total is paid by gift card and the rest paid by another available payment method
     *
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/GiftCardAccount/_files/giftcardaccount.php
     */
    public function testCheckOutWhenGiftCardPartiallyPaysCartTotal()
    {
        $quantity = 2;

        $sku = 'simple_product';
        $giftCardCode ='giftcardaccount_fixture';
        $cartId = $this->createEmptyCart();
        $this->setGuestEmailOnCart($cartId);
        $this->addProductToCart($cartId, $quantity, $sku);

        $this->setBillingAddress($cartId);
        $shippingMethod = $this->setShippingAddress($cartId, 'TX');

        $this->setShippingMethod($cartId, $shippingMethod);
        $this->applyGiftCardToCart($cartId, $giftCardCode);
        $paymentMethodCode = 'checkmo';
        $this->setPaymentMethod($cartId, $paymentMethodCode);

        $this->placeOrder($cartId);
        $giftCardAccountQuery = $this->getGiftCardAccountQuery($giftCardCode);
        $result = $this->graphQlQuery($giftCardAccountQuery);
        //check the balance left on the card once the order is placed after gift card is applied
        $this->assertEquals(0, $result['giftCardAccount']['balance']['value']);
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
        $response = $this->graphQlMutation($query);
        self::assertArrayHasKey('createEmptyCart', $response);
        self::assertNotEmpty($response['createEmptyCart']);

        return $response['createEmptyCart'];
    }

    /**
     * @param string $cartId
     * @return void
     */
    private function setGuestEmailOnCart(string $cartId): void
    {
        $query = <<<QUERY
mutation {
  setGuestEmailOnCart(
    input: {
      cart_id: "{$cartId}"
      email: "customer@example.com"
    }
  ) {
    cart {
      email
    }
  }
}
QUERY;
        $this->graphQlMutation($query);
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
        $this->graphQlMutation($query);
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
        $this->graphQlMutation($query);
    }

    /**
     * @param string $cartId
     * @param string $state
     * @return array
     */
    private function setShippingAddress(string $cartId, string $state): array
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
            region: "{$state}"
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
        $response = $this->graphQlMutation($query);
        self::assertArrayHasKey('setShippingAddressesOnCart', $response);
        self::assertArrayHasKey('cart', $response['setShippingAddressesOnCart']);
        self::assertArrayHasKey('shipping_addresses', $response['setShippingAddressesOnCart']['cart']);
        self::assertCount(1, $response['setShippingAddressesOnCart']['cart']['shipping_addresses']);

        $shippingAddress = current($response['setShippingAddressesOnCart']['cart']['shipping_addresses']);
        self::assertArrayHasKey('available_shipping_methods', $shippingAddress);
        self::assertCount(1, $shippingAddress['available_shipping_methods']);

        $availableShippingMethod = current($shippingAddress['available_shipping_methods']);
        self::assertArrayHasKey('carrier_code', $availableShippingMethod);
        self::assertNotEmpty($availableShippingMethod['carrier_code']);

        self::assertArrayHasKey('method_code', $availableShippingMethod);
        self::assertNotEmpty($availableShippingMethod['method_code']);

        self::assertArrayHasKey('amount', $availableShippingMethod);
        self::assertArrayHasKey('value', $availableShippingMethod['amount']);
        self::assertNotEmpty($availableShippingMethod['amount']['value']);

        return $availableShippingMethod;
    }

    /**
     * @param string $cartId
     * @param string $method
     * @return void
     */
    private function checkAvailablePaymentMethod(string $cartId, string $method, bool $checkExists): void
    {
        $query = <<<QUERY
query {
  cart(cart_id:"{$cartId}") {
    available_payment_methods {
      code
      title
    }
  }
}
QUERY;
        $response = $this->graphQlMutation($query);
        $paymentMethods = $response['cart']['available_payment_methods'] ?? [];
        $freeFound = (bool)array_search($method, array_column($paymentMethods, 'code'));
        self::assertEquals($checkExists, $freeFound);
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
        $response = $this->graphQlMutation($query);
        self::assertArrayHasKey('setShippingMethodsOnCart', $response);
        self::assertArrayHasKey('cart', $response['setShippingMethodsOnCart']);
        self::assertArrayHasKey('available_payment_methods', $response['setShippingMethodsOnCart']['cart']);
        self::assertGreaterThan(0, $response['setShippingMethodsOnCart']['cart']['available_payment_methods']);

        $availablePaymentMethod = current($response['setShippingMethodsOnCart']['cart']['available_payment_methods']);
        self::assertArrayHasKey('code', $availablePaymentMethod);
        self::assertNotEmpty($availablePaymentMethod['code']);
        self::assertArrayHasKey('title', $availablePaymentMethod);
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
        $this->graphQlMutation($query);
    }

    private function applyGiftCardToCart($cartId, $giftCardCode)
    {
        $query = <<<QUERY
mutation {
  applyGiftCardToCart(input: {cart_id: "$cartId", gift_card_code: "$giftCardCode"}) {
    cart {
      applied_gift_cards {
        code
        applied_balance {
          currency
          value
        }
        expiration_date
        current_balance {
          currency
          value
        }
      }
    }
  }
}
QUERY;
        $response = $this->graphQlMutation($query);
        self::assertArrayHasKey('applyGiftCardToCart', $response);
        self::assertEquals($giftCardCode, $response['applyGiftCardToCart']['cart']['applied_gift_cards'][0]['code']);
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
        $response = $this->graphQlMutation($query);
        self::assertArrayHasKey('placeOrder', $response);
        self::assertArrayHasKey('order', $response['placeOrder']);
        self::assertArrayHasKey('order_id', $response['placeOrder']['order']);
        self::assertNotEmpty($response['placeOrder']['order']['order_id']);
    }

    /**
     * Get query string for giftCardAccount query
     *
     * @param $code
     * @return string
     */
    private function getGiftCardAccountQuery($code)
    {
        return <<<QUERY
{
  giftCardAccount(input: {
    gift_card_code: "{$code}"
  }){
    code
    balance {
      currency
      value
    }
    expiration_date
  }
}
QUERY;
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
    private function deleteOrder()
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
