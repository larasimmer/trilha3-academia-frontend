<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\GiftCard;

use Magento\Bundle\Model\Product\Type;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\AuthenticationException;
use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard;
use Magento\GiftCard\Model\Giftcard\Option;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\Wishlist\Model\Item;
use Magento\Wishlist\Model\WishlistFactory;

/**
 * Test coverage for adding a gift card product to wishlist
 */
class AddGiftCardToWishlistTest extends GraphQlAbstract
{
    /**
     * @var CustomerTokenServiceInterface
     */
    private $customerTokenService;

    /**
     * @var WishlistFactory
     */
    private $wishlistFactory;

    /**
     * @var mixed
     */
    private $productRepository;

    /**
     * Set Up
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->customerTokenService = $objectManager->get(CustomerTokenServiceInterface::class);
        $this->wishlistFactory = $objectManager->get(WishlistFactory::class);
        $this->productRepository = $objectManager->get(ProductRepositoryInterface::class);
    }

    /**
     * @magentoConfigFixture default_store wishlist/general/active 1
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/GiftCard/_files/gift_card_with_open_amount.php
     *
     * @param int $amount
     *
     * @dataProvider giftCardAmountProvider
     */
    public function testAddGiftCardProductWithAmountToWishlist(int $amount): void
    {
        $sku = 'gift-card-with-open-amount';
        $product = $this->productRepository->get($sku);
        $customerId = 1;
        $qty = 2;

        /** @var Type $typeInstance */
        $typeInstance = $product->getTypeInstance();
        $typeInstance->setStoreFilter($product->getStoreId(), $product);

        $giftCardQuery = $this->getGiftCardOptionsQuery($sku);
        $response = $this->graphQlQuery($giftCardQuery);

        $giftCardAmounts = $response['products']['items'][0]['giftcard_amounts'];
        $giftCardOptions = $response['products']['items'][0]['gift_card_options'];
        $selectedOptions = '';

        foreach ($giftCardAmounts as $giftCardAmount) {
            if ((int) $giftCardAmount['value'] === $amount) {
                $selectedOptions = 'selected_options: ["'.$giftCardAmount['uid'].'"]';
            }
        }

        $giftCardInformation = $this->prepareGiftCardInformation($giftCardOptions);
        $customAmount = null;
        if (empty($selectedOptions)) {
            $giftCardInformation[Option::KEY_CUSTOM_GIFTCARD_AMOUNT] = [
                'uid' => $this->generateUid(Giftcard::TYPE_GIFTCARD . '/' . Option::KEY_CUSTOM_GIFTCARD_AMOUNT),
                'value' => $amount
            ];
            $query = $this->getEnteredAmountQuery($sku, $qty, $giftCardInformation);
            $customAmount = true;
        } else {
            $query = $this->getSelectedAmountQuery($sku, $qty, $selectedOptions, $giftCardInformation);
        }

        $response = $this->graphQlMutation($query, [], '', $this->getHeaderMap());
        $wishlist = $this->wishlistFactory->create()->loadByCustomerId($customerId, true);
        /** @var Item $item */
        $item = $wishlist->getItemCollection()->getFirstItem();

        $this->assertArrayHasKey('addProductsToWishlist', $response);
        $this->assertArrayHasKey('wishlist', $response['addProductsToWishlist']);
        $response = $response['addProductsToWishlist']['wishlist'];
        $wishlistId = $response['id'];
        $this->assertEquals($wishlist->getItemsCount(), $response['items_count']);
        $this->assertEquals($wishlist->getSharingCode(), $response['sharing_code']);
        $this->assertEquals($wishlist->getUpdatedAt(), $response['updated_at']);
        $this->assertEquals((int) $item->getQty(), $response['items_v2']['items'][0]['quantity']);
        $wishlistItemId = $response['items_v2']['items'][0]['id'];
        $this->assertEquals($item->getAddedAt(), $response['items_v2']['items'][0]['added_at']);
        $giftCardOptionsResponse = $response['items_v2']['items'][0]['gift_card_options'];
        $this->assertEquals('Sender 1', $giftCardOptionsResponse['sender_name']);
        $this->assertEquals('sender1@email.com', $giftCardOptionsResponse['sender_email']);
        $this->assertEquals('Recipient 1', $giftCardOptionsResponse['recipient_name']);
        $this->assertEquals('recipient1@email.com', $giftCardOptionsResponse['recipient_email']);
        if ($customAmount) {
            $this->assertEquals(250, $giftCardOptionsResponse['custom_giftcard_amount']['value']);
            $this->assertEquals('USD', $giftCardOptionsResponse['custom_giftcard_amount']['currency']);

        } else {
            $this->assertEquals(120, $giftCardOptionsResponse['amount']['value']);
            $this->assertEquals('USD', $giftCardOptionsResponse['amount']['currency']);
        }
        $wishlistToCartQuery = $this->addGiftCardFromWishlistToCart($wishlistId, $wishlistItemId);
        $response = $this->graphQlMutation($wishlistToCartQuery, [], '', $this->getHeaderMap());
        $this->assertArrayHasKey('addWishlistItemsToCart', $response);
        $this->assertArrayHasKey('status', $response['addWishlistItemsToCart']);
        $this->assertEquals(true, $response['addWishlistItemsToCart']['status']);
        $this->assertEmpty($response['addWishlistItemsToCart']['add_wishlist_items_to_cart_user_errors']);
    }

    /**
     * Providing amount for the gift product
     *
     * @return array
     */
    public function giftCardAmountProvider(): array
    {
        return [
            'Fixed amount value' => [
                120
            ],
            'Open amount value' => [
                250
            ]
        ];
    }

    /**
     * Authentication header map
     *
     * @param string $username
     * @param string $password
     *
     * @return array
     *
     * @throws AuthenticationException
     */
    private function getHeaderMap(string $username = 'customer@example.com', string $password = 'password'): array
    {
        $customerToken = $this->customerTokenService->createCustomerAccessToken($username, $password);

        return ['Authorization' => 'Bearer ' . $customerToken];
    }

    /**
     * Returns GraphQl mutation string with selected amount
     *
     * @param string $sku
     * @param int $qty
     * @param string $selectedOptionsQuery
     * @param array $giftCardInformation
     * @param int $wishlistId
     *
     * @return string
     */
    private function getSelectedAmountQuery(
        string $sku,
        int $qty,
        string $selectedOptionsQuery,
        array $giftCardInformation,
        int $wishlistId = 0
    ): string {
        return <<<MUTATION
mutation {
  addProductsToWishlist(
    wishlistId: {$wishlistId},
    wishlistItems: [
      {
        sku: "{$sku}"
        quantity: {$qty}
        {$selectedOptionsQuery}
        entered_options: [{
      	   uid: "{$giftCardInformation[Option::KEY_SENDER_NAME]['uid']}"
           value: "{$giftCardInformation[Option::KEY_SENDER_NAME]['value']}"
      	}, {
      	   uid: "{$giftCardInformation[Option::KEY_SENDER_EMAIL]['uid']}"
           value: "{$giftCardInformation[Option::KEY_SENDER_EMAIL]['value']}"
      	}, {
      	   uid: "{$giftCardInformation[Option::KEY_RECIPIENT_NAME]['uid']}"
           value: "{$giftCardInformation[Option::KEY_RECIPIENT_NAME]['value']}"
      	}, {
      	   uid: "{$giftCardInformation[Option::KEY_RECIPIENT_EMAIL]['uid']}"
           value: "{$giftCardInformation[Option::KEY_RECIPIENT_EMAIL]['value']}"
      	}, {
      	   uid: "{$giftCardInformation[Option::KEY_MESSAGE]['uid']}"
           value: "{$giftCardInformation[Option::KEY_MESSAGE]['value']}"
      	}]
      }
    ]
) {
    user_errors {
      code
      message
    }
    wishlist {
      id
      sharing_code
      items_count
      updated_at
      items_v2 {
      items {
          id
        description
        quantity
        added_at
        ... on GiftCardWishlistItem {
          gift_card_options {
            sender_name
            recipient_name
            sender_email
            recipient_email
            message
            amount
            {
              value
              currency
            }
            custom_giftcard_amount
            {
              value
              currency
            }
          }
        }
      }
      }
    }
  }
}

MUTATION;
    }

    /**
     * Returns GraphQl mutation string with entered amount
     *
     * @param string $sku
     * @param int $qty
     * @param array $giftCardInformation
     * @param int $wishlistId
     *
     * @return string
     */
    private function getEnteredAmountQuery(
        string $sku,
        int $qty,
        array $giftCardInformation,
        int $wishlistId = 0
    ): string {
        return <<<MUTATION
mutation {
  addProductsToWishlist(
    wishlistId: {$wishlistId},
    wishlistItems: [
      {
        sku: "{$sku}"
        quantity: {$qty}
        entered_options: [{
      	   uid: "{$giftCardInformation[Option::KEY_CUSTOM_GIFTCARD_AMOUNT]['uid']}"
           value: "{$giftCardInformation[Option::KEY_CUSTOM_GIFTCARD_AMOUNT]['value']}"
      	}, {
      	   uid: "{$giftCardInformation[Option::KEY_SENDER_NAME]['uid']}"
           value: "{$giftCardInformation[Option::KEY_SENDER_NAME]['value']}"
      	}, {
      	   uid: "{$giftCardInformation[Option::KEY_SENDER_EMAIL]['uid']}"
           value: "{$giftCardInformation[Option::KEY_SENDER_EMAIL]['value']}"
      	}, {
      	   uid: "{$giftCardInformation[Option::KEY_RECIPIENT_NAME]['uid']}"
           value: "{$giftCardInformation[Option::KEY_RECIPIENT_NAME]['value']}"
      	}, {
      	   uid: "{$giftCardInformation[Option::KEY_RECIPIENT_EMAIL]['uid']}"
           value: "{$giftCardInformation[Option::KEY_RECIPIENT_EMAIL]['value']}"
      	}, {
      	   uid: "{$giftCardInformation[Option::KEY_MESSAGE]['uid']}"
           value: "{$giftCardInformation[Option::KEY_MESSAGE]['value']}"
      	}]
      }
    ]
) {
    user_errors {
      code
      message
    }
    wishlist {
      id
      sharing_code
      items_count
      updated_at
      items_v2 {
      items {
          id
        description
        quantity
        added_at
        ... on GiftCardWishlistItem {
          gift_card_options {
            sender_name
            recipient_name
            sender_email
            recipient_email
            message
            amount
            {
              value
              currency
            }
            custom_giftcard_amount
            {
              value
              currency
            }
          }
        }
      }
      }
    }
  }
}

MUTATION;
    }

    /**
     * Get gift card options query
     *
     * @param string $sku
     *
     * @return string
     */
    private function getGiftCardOptionsQuery(string $sku): string
    {
        return <<<QUERY
query {
  products(filter: { sku: { eq: "$sku" } }) {
    items {
      sku
      ... on GiftCardProduct {
        giftcard_amounts {
          uid
          value_id
          website_id
          value
          attribute_id
          website_value
        }
        gift_card_options {
          title
          required
          ... on CustomizableFieldOption {
            value: value {
              uid
            }
          }
        }
      }
    }
  }
}
QUERY;
    }

    /**
     * Gift card options
     *
     * @return array
     */
    private function giftCardOptionDataProvider(): array
    {
        return [
            'sender_name' => 'Sender 1',
            'sender_email' => 'sender1@email.com',
            'recipient_name' => 'Recipient 1',
            'recipient_email' => 'recipient1@email.com',
            'message' => 'Message 1',
        ];
    }

    /**
     * Preparing the associative gift card information
     *
     * @param array $giftCardOptions
     *
     * @return array
     */
    private function prepareGiftCardInformation(array $giftCardOptions): array
    {
        $giftCardInformation = [];

        foreach ($giftCardOptions as $giftCardOption) {
            $key = str_replace(' ', '_', strtolower($giftCardOption['title']));

            foreach ($this->giftCardOptionDataProvider() as $optionKey => $optionData) {
                if ($optionKey === $key) {
                    $giftCardInformation[Giftcard::TYPE_GIFTCARD . '_' . $key] = [
                        'uid' => $giftCardOption['value']['uid'],
                        'value' => $optionData
                    ];
                }
            }
        }

        return $giftCardInformation;
    }

    /**
     * Generating gift card key uid
     *
     * @param string $key
     *
     * @return string
     */
    private function generateUid(string $key): string
    {
        return base64_encode($key);
    }

    /**
     *
     * @param int $wishlistId
     * @param $wishlistItemId
     * @return string
     */
    private function addGiftCardFromWishlistToCart($wishlistId, $wishlistItemId): string
    {
        return <<<MUTATION
mutation{
addWishlistItemsToCart
  (
    wishlistId:{$wishlistId}
    wishlistItemIds:[{$wishlistItemId}]
  )
  {
    status
    add_wishlist_items_to_cart_user_errors {
      message
      code
    }

  }
}
MUTATION;
    }
}
