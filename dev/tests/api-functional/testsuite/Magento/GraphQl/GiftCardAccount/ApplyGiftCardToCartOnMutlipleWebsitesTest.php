<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\GiftCardAccount;

use Magento\GraphQl\Quote\GetMaskedQuoteIdByReservedOrderId;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test apply gift card on multiple websites.
 */
class ApplyGiftCardToCartOnMutlipleWebsitesTest extends GraphQlAbstract
{
    /**
     * @var GetMaskedQuoteIdByReservedOrderId
     */
    private $getMaskedQuoteIdByReservedOrderId;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->getMaskedQuoteIdByReservedOrderId = $objectManager->get(GetMaskedQuoteIdByReservedOrderId::class);
    }

    /**
     * Test to apply first gift card on default website.
     *
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/guest/create_empty_cart.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     * @magentoApiDataFixture Magento/GiftCardAccount/_files/giftcardaccount.php
     */
    public function testApplyFirstGiftCardToCartOnDefaultWebsite()
    {
        $giftCardCode ='giftcardaccount_fixture';
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('test_quote');

        $query = $this->getApplyGiftCardToCart($maskedQuoteId, $giftCardCode);
        $headers = ['Store' => 'default'];

        $response = $this->graphQlMutation($query, [], '', $headers);
        $this->assertEquals(
            $giftCardCode,
            $response['applyGiftCardToCart']['cart']['applied_gift_cards'][0]['code']
        );
        $this->assertEquals(
            9.99,
            $response['applyGiftCardToCart']['cart']['applied_gift_cards'][0]['applied_balance']['value']
        );
    }

    /**
     * Test to apply first gift card on second website.
     *
     * @magentoApiDataFixture Magento/GiftCardAccount/_files/giftcardaccount.php
     * @magentoApiDataFixture Magento/GiftCardAccount/_files/create_giftcardaccount_with_second_website_and_product.php
     *
     */
    public function testApplyFirstGiftCardToCartOnSecondWebsite()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('GraphQL response contains errors: The gift card code couldn\'t be added. Verify your information and try again.');

        $giftCardCode ='giftcardaccount_fixture';

        $quantity = 2;
        $sku = 'simple_two';
        $cartId = $this->createEmptyCart();
        $this->setGuestEmailOnCart($cartId);
        $this->addProductToCart($cartId, $quantity, $sku);

        $query = $this->getApplyGiftCardToCart($cartId, $giftCardCode);
        $headers = ['Store' => 'fixture_second_store'];
        $this->graphQlMutation($query, [], '', $headers);
    }

    /**
     * Test to apply second gift card on second website.
     *
     * @magentoApiDataFixture Magento/GiftCardAccount/_files/create_giftcardaccount_with_second_website_and_product.php
     */
    public function testApplySecondGiftCardToCartOnSecondWebsite()
    {
        $giftCardCode ='gift_card_account_two';

        $quantity = 2;
        $sku = 'simple_two';

        $cartId = $this->createEmptyCart();
        $this->setGuestEmailOnCart($cartId);
        $this->addProductToCart($cartId, $quantity, $sku);

        $query = $this->getApplyGiftCardToCart($cartId, $giftCardCode);
        $headers = ['Store' => 'fixture_second_store'];

        $response = $this->graphQlMutation($query, [], '', $headers);
        $this->assertEquals(
            $giftCardCode,
            $response['applyGiftCardToCart']['cart']['applied_gift_cards'][0]['code']
        );
        $this->assertEquals(
            9.99,
            $response['applyGiftCardToCart']['cart']['applied_gift_cards'][0]['applied_balance']['value']
        );
    }

    /**
     * Test to apply second gift card on default website.
     *
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/guest/create_empty_cart.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     * @magentoApiDataFixture Magento/GiftCardAccount/_files/create_giftcardaccount_with_second_website_and_product.php
     *
     */
    public function testApplySecondGiftCardToCartOnDefaultWebsite()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('GraphQL response contains errors: The gift card code couldn\'t be added. Verify your information and try again.');

        $giftCardCode ='gift_card_account_two';
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('test_quote');

        $query = $this->getApplyGiftCardToCart($maskedQuoteId, $giftCardCode);
        $headers = ['Store' => 'default'];
        $this->graphQlMutation($query, [], '', $headers);
    }

    /**
     * Test query response when first gift card is queried on second website.
     *
     * @magentoApiDataFixture Magento/GiftCardAccount/_files/giftcardaccount.php
     * @magentoApiDataFixture Magento/GiftCardAccount/_files/create_giftcardaccount_with_second_website_and_product.php
     *
     */
    public function testQueryFirstGiftCardAccountOnSecondWebsite()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('GraphQL response contains errors: Invalid gift card');

        $giftCardCode = 'giftcardaccount_fixture';
        $query = $this->getGiftCardAccountQuery($giftCardCode);

        $headers = ['Store' => 'fixture_second_store'];
        $this->graphQlQuery($query, [], '', $headers);
    }

    /**
     * Test query response when second gift card is queried on default website.
     *
     * @magentoApiDataFixture Magento/GiftCardAccount/_files/create_giftcardaccount_with_second_website_and_product.php
     *
     */
    public function testQuerySecondGiftCardAccountOnDefaultWebsite()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('GraphQL response contains errors: Invalid gift card');

        $giftCardCode = 'gift_card_account_two';
        $query = $this->getGiftCardAccountQuery($giftCardCode);

        $headers = ['Store' => 'default'];
        $this->graphQlQuery($query, [], '', $headers);
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
     * Get query strings for the applyGiftCardToCart mutation
     *
     * @param string $maskedQuoteId
     * @param string $giftCardCode
     * @return string
     */
    private function getApplyGiftCardToCart(string $maskedQuoteId, string $giftCardCode): string
    {
        return <<<QUERY
mutation {
  applyGiftCardToCart(input: {cart_id: "$maskedQuoteId", gift_card_code: "$giftCardCode"}) {
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
    }
    
    /**
     * Create an empty cart
     *
     * @return string
     */
    private function createEmptyCart(): string
    {
        $query = <<<QUERY
mutation {
  createEmptyCart
}
QUERY;
        $headers = ['Store' => 'fixture_second_store'];
        $response = $this->graphQlMutation($query, [], '', $headers);
        self::assertArrayHasKey('createEmptyCart', $response);
        self::assertNotEmpty($response['createEmptyCart']);

        return $response['createEmptyCart'];
    }

    /**
     * Set guest email on cart
     *
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
        $headers = ['Store' => 'fixture_second_store'];
        $this->graphQlMutation($query, [], '', $headers);
    }

    /**
     * Add simple products to cart
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
        $headers = ['Store' => 'fixture_second_store'];
        $this->graphQlMutation($query, [], '', $headers);
    }
}
