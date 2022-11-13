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
 * Test cart mutations and queries as they pertain to applied gift cards
 */
class CartGiftCardAccountTest extends GraphQlAbstract
{
    /**
     * @var GetMaskedQuoteIdByReservedOrderId
     */
    private $getMaskedQuoteIdByReservedOrderId;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->getMaskedQuoteIdByReservedOrderId = $objectManager->get(GetMaskedQuoteIdByReservedOrderId::class);
    }

    /**
     * Remove all items from a cart that has a gift card applied
     *
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/guest/create_empty_cart.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     * @magentoApiDataFixture Magento/GiftCardAccount/_files/giftcardaccount.php
     */
    public function testRemoveAllItemsFromCartWithGiftCard()
    {
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('test_quote');
        $giftCardCode = 'giftcardaccount_fixture';

        $this->applyGiftCardToCart($maskedQuoteId, $giftCardCode);
        $cartItemId = $this->getCartItemId($maskedQuoteId);

        $removeItemQuery = <<<QUERY
mutation {
  removeItemFromCart(input: {cart_id: "$maskedQuoteId", cart_item_id: $cartItemId}) {
    cart {
      id
      items {
        id
        product {
          name
        }
        quantity
      }
      applied_gift_cards {
        code
        current_balance {
          currency
          value
        }
        applied_balance {
          currency
          value
        }
      }
    }
  }
}

QUERY;

        $result = $this->graphQlMutation($removeItemQuery);
        $this->assertArrayNotHasKey('errors', $result);
        $this->assertCount(0, $result['removeItemFromCart']['cart']['items']);
        $this->assertCount(1, $result['removeItemFromCart']['cart']['applied_gift_cards']);
        $this->assertEquals(
            '9.99',
            $result['removeItemFromCart']['cart']['applied_gift_cards'][0]['current_balance']['value']
        );
        $this->assertEquals(
            '0',
            $result['removeItemFromCart']['cart']['applied_gift_cards'][0]['applied_balance']['value']
        );
    }

    /**
     * Query cart to get cart item id
     *
     * @param string $cartId
     * @return string
     */
    private function getCartItemId(string $cartId): string
    {
        $cartQuery = <<<QUERY
{
  cart(cart_id: "$cartId") {
    id
    items {
      id
    }
  }
}
QUERY;

        $result = $this->graphQlQuery($cartQuery);
        $this->assertArrayNotHasKey('errors', $result);
        $this->assertCount(1, $result['cart']['items']);

        return $result['cart']['items'][0]['id'];
    }

    /**
     * Apply gift card to cart
     *
     * @param string $cartId
     * @param string $giftCardCode
     * @return bool
     */
    private function applyGiftCardToCart(string $cartId, string $giftCardCode): bool
    {
        $applyQuery = <<<QUERY
mutation{
  applyGiftCardToCart(input: {cart_id: "$cartId", gift_card_code: "$giftCardCode"}){
    cart{
      applied_gift_cards{
        code
        current_balance{
          value
        }
        applied_balance{
          value
        }
      }
    }
  }
}
QUERY;

        $result = $this->graphQlMutation($applyQuery);
        $this->assertArrayNotHasKey('errors', $result);
        $this->assertCount(1, $result['applyGiftCardToCart']['cart']['applied_gift_cards']);
        $this->assertEquals($giftCardCode, $result['applyGiftCardToCart']['cart']['applied_gift_cards'][0]['code']);
        $this->assertEquals(
            '9.99',
            $result['applyGiftCardToCart']['cart']['applied_gift_cards'][0]['applied_balance']['value']
        );

        return true;
    }
}
