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
 * Test to check removing of the applied store credit from the Cart functionality
 */
class RemoveStoreCreditFromCartTest extends GraphQlAbstract
{
    /**
     * @var GetMaskedQuoteIdByReservedOrderId
     */
    private $getMaskedQuoteIdByReservedOrderId;

    /**
     * @var  CustomerTokenServiceInterface
     */
    private $customerTokenService;

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
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/CustomerBalance/_files/customer_balance_default_website.php
     */
    public function testRemoveAppliedStoreCreditFromCart()
    {
        $quantity = 3;
        $sku = 'simple_product';
        $cartId = $this->createEmptyCart();
        $this->addProductToCart($cartId, $quantity, $sku);

        $applyQuery = $this->applyStoreCreditQuery($cartId);
        $this->graphQlMutation($applyQuery, [], '', $this->getHeaderMap());

        $removeQuery = $this->removeStoreCreditQuery($cartId);
        $response = $this->graphQlMutation($removeQuery, [], '', $this->getHeaderMap());
        $this->assertArrayHasKey('removeStoreCreditFromCart', $response);

        $appliedStoreCredit = $response['removeStoreCreditFromCart']['cart']['applied_store_credit'];
        $this->assertNotNull($appliedStoreCredit['applied_balance']);

        $this->assertEquals('USD', $appliedStoreCredit['applied_balance']['currency']);
        $this->assertEquals(0.00, $appliedStoreCredit['applied_balance']['value']);

        $this->assertEquals('USD', $appliedStoreCredit['current_balance']['currency']);
        $this->assertEquals(50.00, $appliedStoreCredit['current_balance']['value']);
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
     * @return string
     */
    private function removeStoreCreditQuery(string $cartId): string
    {
        return <<<QUERY
mutation
{
  removeStoreCreditFromCart(input:{cart_id:"$cartId"})
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

        $headerMap = ['Authorization' => 'Bearer ' . $customerToken];
        return $headerMap;
    }
}
