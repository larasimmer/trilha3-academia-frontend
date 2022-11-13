<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\GiftWrapping\Cart\Item;

use Exception;
use Magento\GiftWrapping\Model\ResourceModel\Wrapping as GiftWrappingResource;
use Magento\GiftWrapping\Model\Wrapping as GiftWrapping;
use Magento\GraphQl\Quote\GetMaskedQuoteIdByReservedOrderId;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

class SetGiftWrappingTest extends GraphQlAbstract
{
    /**
     * @var GetMaskedQuoteIdByReservedOrderId
     */
    private $getMaskedQuoteIdByReservedOrderId;

    /**
     * @var GiftWrapping
     */
    private $giftWrappingModel;

    /**
     * @var GiftWrappingResource
     */
    private $giftWrappingResource;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->getMaskedQuoteIdByReservedOrderId = $objectManager->get(GetMaskedQuoteIdByReservedOrderId::class);
        $this->giftWrappingModel = $objectManager->get(GiftWrapping::class);
        $this->giftWrappingResource = $objectManager->get(GiftWrappingResource::class);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @magentoApiDataFixture Magento/GiftWrapping/_files/quote/quote_with_selected_gift_wrapping.php
     */
    public function testSetGiftWrappingForItem()
    {
        $quoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('test_quote_with_selected_gift_wrapping');
        $cartItemId = $this->getCartItemId($quoteId);

        $allGiftWrappings = <<<QUERY
{
   cart(cart_id: "$quoteId") {
    available_gift_wrappings  {
        id
    }
  }
}
QUERY;

        $response = $this->graphQlQuery($allGiftWrappings);
        $this->assertArrayHasKey(2, $response['cart']['available_gift_wrappings']);
        $giftWrappingId = $response['cart']['available_gift_wrappings'][2]['id'];

        $query =  <<<QUERY
mutation {
  updateCartItems(
    input: {
      cart_id: "$quoteId",
      cart_items: [
        {
          cart_item_id: $cartItemId
          quantity: 3
          gift_wrapping_id: "{$giftWrappingId}"
        }
      ]
    }
  ) {
    cart {
      items {
        id
        product {
         name
        }
        quantity
          ... on SimpleCartItem {
          gift_message {
            from
            to
            message
          }
            gift_wrapping {
                id
                uid
                design
                image {
                  label
                  url
                }
            }
        }
     }
    }
  }
}
QUERY;
        $result = $this->graphQlMutation($query);
        $item = $result['updateCartItems']['cart']['items'][0];
        $this->assertSame('Simple Product', $item['product']['name']);
        $this->assertSame(base64_encode($item['gift_wrapping']['id']), $item['gift_wrapping']['uid']);
        $this->assertSame('Test Wrapping 3', $item['gift_wrapping']['design']);
        $this->assertSame('image3.png', $item['gift_wrapping']['image']['label']);

        $query =  <<<QUERY
mutation {
  updateCartItems(
    input: {
      cart_id: "$quoteId",
      cart_items: [
        {
          cart_item_id: $cartItemId
          quantity: 3
          gift_wrapping_id: null
        }
      ]
    }
  ) {
    cart {
      items {
        id
        product {
         name
        }
        quantity
          ... on SimpleCartItem {
            gift_wrapping {
                id
                uid
                design
                image {
                  label
                  url
                }
            }
        }
     }
    }
  }
}
QUERY;
        $resultWithGiftWrappingIdNull = $this->graphQlMutation($query);
        $item = $resultWithGiftWrappingIdNull['updateCartItems']['cart']['items'][0];
        $this->assertSame('Simple Product', $item['product']['name']);
        $this->assertSame(null, $item['gift_wrapping']);
    }

    /**
     * Query cart to get cart item id
     *
     * @param string $cartId
     *
     * @return string
     * @throws Exception
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
}
