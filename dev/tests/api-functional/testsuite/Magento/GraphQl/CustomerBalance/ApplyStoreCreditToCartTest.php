<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\CustomerBalance;

use Magento\GraphQl\Quote\GetMaskedQuoteIdByReservedOrderId;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test Apply Store credit to Cart functionality
 */
class ApplyStoreCreditToCartTest extends GraphQlAbstract
{
    /**
     * @var GetMaskedQuoteIdByReservedOrderId
     */
    private $getMaskedQuoteIdByReservedOrderId;

    /** @var  CustomerTokenServiceInterface */
    private $customerTokenService;

    private $storeCode = 'default';

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->getMaskedQuoteIdByReservedOrderId = $objectManager->get(GetMaskedQuoteIdByReservedOrderId::class);
        $this->customerTokenService = $objectManager->get(CustomerTokenServiceInterface::class);
    }

    /**
     * Store credit balance is greater than the cart total
     *
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/CustomerBalance/_files/customer_balance_default_website.php
     */
    public function testApplyStoreCreditToCart()
    {
        $quantity = 3;
        $sku = 'simple_product';
        $cartId = $this->createEmptyCart();
        $this->addProductToCart($cartId, $quantity, $sku);
        $query = $this->applyStoreCreditQuery($cartId);
        $response = $this->graphQlMutation($query, [], '', $this->getHeaderMap());
        self::assertArrayHasKey('applyStoreCreditToCart', $response);
        $appliedStoreCredit = $response['applyStoreCreditToCart']['cart']['applied_store_credit'];
        $this->assertTrue($appliedStoreCredit['enabled']);
        self::assertNotNull($appliedStoreCredit);
        self::assertNotEmpty($appliedStoreCredit['applied_balance'], "Failed: 'applied_balance' must not be empty");

        self::assertEquals('USD', $appliedStoreCredit['applied_balance']['currency']);
        self::assertEquals(30.00, $appliedStoreCredit['applied_balance']['value']);

        self::assertEquals('USD', $appliedStoreCredit['current_balance']['currency']);
        self::assertEquals(50.00, $appliedStoreCredit['current_balance']['value']);
    }

    /**
     * Store credit balance is lesser than the cart total
     *
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/CustomerBalance/_files/customer_balance_default_website.php
     */
    public function testApplyStoreCreditWithBalanceLesserThanCartTotal()
    {
        $quantity = 6;
        $sku = 'simple_product';
        $cartId = $this->createEmptyCart();
        $this->addProductToCart($cartId, $quantity, $sku);
        $query = $this->applyStoreCreditQuery($cartId);
        $response = $this->graphQlMutation($query, [], '', $this->getHeaderMap());
        self::assertArrayHasKey('applyStoreCreditToCart', $response);
        $appliedStoreCredit = $response['applyStoreCreditToCart']['cart']['applied_store_credit'];
        self::assertNotNull($appliedStoreCredit);
        self::assertNotEmpty($appliedStoreCredit['applied_balance'], "Failed: 'applied_balance' must not be empty");

        self::assertEquals('USD', $appliedStoreCredit['applied_balance']['currency']);
        self::assertEquals(50.00, $appliedStoreCredit['applied_balance']['value']);

        self::assertEquals('USD', $appliedStoreCredit['current_balance']['currency']);
        self::assertEquals(50.00, $appliedStoreCredit['current_balance']['value']);
    }

    /**
     * This test verifies the case where the store credit created for the specific store is applied when the
     * customer account is shared globally
     *
     * @magentoApiDataFixture Magento/CustomerBalance/_files/enable_customer_account_globally.php
     * @magentoApiDataFixture Magento/CustomerBalance/_files/customer_balances_multiple_websites.php
     */
    public function testApplyStoreSpecificStoreCredit()
    {
        $quantity = 2;
        $this->storeCode = 'second_store_view';
        $sku = 'simple_product_second';
        $cartId = $this->createEmptyCart();
        $this->addProductToCart($cartId, $quantity, $sku);
        $query = $this->applyStoreCreditQuery($cartId);
        $response = $this->graphQlMutation($query, [], '', $this->getHeaderMap());
        self::assertArrayHasKey('applyStoreCreditToCart', $response);
        $appliedStoreCredit = $response['applyStoreCreditToCart']['cart']['applied_store_credit'];
        self::assertNotNull($appliedStoreCredit);
        self::assertNotEmpty($appliedStoreCredit['applied_balance'], "Failed: 'applied_balance' must not be empty");

        self::assertEquals('USD', $appliedStoreCredit['applied_balance']['currency']);
        self::assertEquals(40.00, $appliedStoreCredit['applied_balance']['value']);

        self::assertEquals('USD', $appliedStoreCredit['current_balance']['currency']);
        self::assertEquals(150.00, $appliedStoreCredit['current_balance']['value']);
    }

    /**
     * Exception should be returned when applying store credit if store credit configuration is disabled from admin
     *
     * @magentoApiDataFixture Magento/CustomerBalance/_files/disable_customer_balance.php
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/CustomerBalance/_files/customer_balance_default_website.php
     */
    public function testApplyStoreCreditWithDisabledConfiguration()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('You cannot add "credit" to the cart.');

        $quantity = 1;
        $sku = 'simple_product';
        $cartId = $this->createEmptyCart();
        $this->addProductToCart($cartId, $quantity, $sku);
        $query = $this->applyStoreCreditQuery($cartId);
        $this->graphQlMutation($query, [], '', $this->getHeaderMap());
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
     * @return string
     */
    private function applyStoreCreditQuery(string $cartId): string
    {
        return <<<QUERY
mutation
{
  applyStoreCreditToCart(input:{cart_id:"$cartId"})
  {
    cart{
      applied_store_credit{
      enabled
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
     * @param string $username
     * @param string $password
     * @return array
     */
    private function getHeaderMap(string $username = 'customer@example.com', string $password = 'password'): array
    {
        $customerToken = $this->customerTokenService->createCustomerAccessToken($username, $password);

        $headerMap = ['Store' => $this->storeCode, 'Authorization' => 'Bearer ' . $customerToken];
        return $headerMap;
    }
}
