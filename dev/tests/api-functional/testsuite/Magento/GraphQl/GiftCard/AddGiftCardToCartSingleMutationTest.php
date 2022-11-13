<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\GiftCard;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\GiftCard\Model\Giftcard\Amount;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResource;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test adding giftcard products to cart using the unified mutation
 */
class AddGiftCardToCartSingleMutationTest extends GraphQlAbstract
{
    /**
     * @var QuoteResource
     */
    private $quoteResource;

    /**
     * @var Quote
     */
    private $quote;

    /**
     * @var QuoteIdToMaskedQuoteIdInterface
     */
    private $quoteIdToMaskedId;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->quoteResource = $objectManager->get(QuoteResource::class);
        $this->quote = $objectManager->create(Quote::class);
        $this->quoteIdToMaskedId = $objectManager->get(QuoteIdToMaskedQuoteIdInterface::class);
        $this->productRepository = $objectManager->get(ProductRepositoryInterface::class);
    }

    /**
     * @magentoApiDataFixture Magento/GiftCard/_files/gift_card_1.php
     * @magentoApiDataFixture Magento/Checkout/_files/active_quote.php
     */
    public function testAddGiftCardToCart()
    {
        $this->quoteResource->load(
            $this->quote,
            'test_order_1',
            'reserved_order_id'
        );
        $sku = 'gift-card-with-amount';

        $queryProducts = $this->getQueryProducts($sku);
        $response = $this->graphQlQuery($queryProducts);
        $giftcardAmounts = $response['products']['items'][0]['giftcard_amounts'];
        $giftCardOptions = $response['products']['items'][0]['gift_card_options'];
        $uidMap = $this->mapUidsToGiftCardOptions($giftCardOptions);

        foreach ($giftcardAmounts as $giftcardAmount) {
            foreach ($this->giftcardOptionDataProvider() as $giftcardOptionData) {
                $giftcardOptionUid = $giftcardAmount['uid'];
                $maskedQuoteId = $this->quoteIdToMaskedId->execute((int) $this->quote->getId());
                $query = $this->getValidQueryAddProductsToCart(
                    $maskedQuoteId,
                    $sku,
                    (string) $giftcardOptionUid,
                    (string) $giftcardOptionData['giftcard_sender_name'],
                    (string) $giftcardOptionData['giftcard_sender_email'],
                    (string) $giftcardOptionData['giftcard_recipient_name'],
                    (string) $giftcardOptionData['giftcard_recipient_email'],
                    (string) $giftcardOptionData['giftcard_message'],
                    $uidMap
                );

                $response = $this->graphQlMutation($query);

                self::assertArrayHasKey('addProductsToCart', $response);
                self::assertArrayHasKey('cart', $response['addProductsToCart']);
                $cart = $response['addProductsToCart']['cart'];
                $giftcardItem = end($cart['items']);
                self::assertEquals($sku, $giftcardItem['product']['sku']);
                self::assertArrayHasKey('amount', $giftcardItem);
                self::assertEquals(
                    (float) $giftcardAmount['value'],
                    (float) $giftcardItem['amount']['value']
                );
                self::assertArrayHasKey('sender_name', $giftcardItem);
                self::assertEquals(
                    (float) $giftcardOptionData['giftcard_sender_name'],
                    (float) $giftcardItem['sender_name']
                );
                self::assertArrayHasKey('sender_email', $giftcardItem);
                self::assertEquals(
                    (float) $giftcardOptionData['giftcard_sender_email'],
                    (float) $giftcardItem['sender_email']
                );
                self::assertArrayHasKey('recipient_name', $giftcardItem);
                self::assertEquals(
                    (float) $giftcardOptionData['giftcard_recipient_name'],
                    (float) $giftcardItem['recipient_name']
                );
                self::assertArrayHasKey('recipient_email', $giftcardItem);
                self::assertEquals(
                    (float) $giftcardOptionData['giftcard_recipient_email'],
                    (float) $giftcardItem['recipient_email']
                );
                self::assertArrayHasKey('message', $giftcardItem);
                self::assertEquals(
                    (float) $giftcardOptionData['giftcard_message'],
                    (float) $giftcardItem['message']
                );
            }
        }
    }

    /**
     * @magentoApiDataFixture Magento/GiftCard/_files/gift_card_1.php
     * @magentoApiDataFixture Magento/Checkout/_files/active_quote.php
     */
    public function testAddGiftCardToCartWithCustomAmount()
    {
        $this->quoteResource->load(
            $this->quote,
            'test_order_1',
            'reserved_order_id'
        );

        $sku = 'gift-card-with-amount';
        $queryProducts = $this->getQueryProducts($sku);
        $response = $this->graphQlQuery($queryProducts);
        $openAmountMin = (int) $response['products']['items'][0]['open_amount_min'];
        $openAmountMax = (int) $response['products']['items'][0]['open_amount_max'];
        $giftCardOptions = $response['products']['items'][0]['gift_card_options'];
        $uidMap = $this->mapUidsToGiftCardOptions($giftCardOptions);
        foreach ($this->giftcardOptionDataProvider() as $giftcardOptionData) {
            $giftcardCustomAmount = rand(
                $openAmountMin,
                $openAmountMax
            );
            $maskedQuoteId = $this->quoteIdToMaskedId->execute((int) $this->quote->getId());
            $query = $this->getValidQueryAddProductsToCartWithCustomAmount(
                (string) $maskedQuoteId,
                (string) $sku,
                (string) $giftcardCustomAmount,
                (string) $giftcardOptionData['giftcard_sender_name'],
                (string) $giftcardOptionData['giftcard_sender_email'],
                (string) $giftcardOptionData['giftcard_recipient_name'],
                (string) $giftcardOptionData['giftcard_recipient_email'],
                (string) $giftcardOptionData['giftcard_message'],
                $uidMap
            );

            $response = $this->graphQlMutation($query);

            self::assertArrayHasKey('addProductsToCart', $response);
            self::assertArrayHasKey('cart', $response['addProductsToCart']);
            $cart = $response['addProductsToCart']['cart'];
            $giftcardItem = end($cart['items']);
            self::assertEquals($sku, $giftcardItem['product']['sku']);
            self::assertArrayHasKey('amount', $giftcardItem);
            self::assertArrayHasKey('sender_name', $giftcardItem);
            self::assertArrayHasKey('sender_email', $giftcardItem);
            self::assertArrayHasKey('recipient_name', $giftcardItem);
            self::assertArrayHasKey('recipient_email', $giftcardItem);
            self::assertArrayHasKey('message', $giftcardItem);
            self::assertEquals(
                (float) $giftcardCustomAmount,
                (float) $giftcardItem['amount']['value']
            );
        }
    }

    /**
     * @magentoApiDataFixture Magento/GiftCard/_files/gift_card_1.php
     * @magentoApiDataFixture Magento/Checkout/_files/active_quote.php
     */
    public function testAddGiftCardToCartWithoutEnteredOptions()
    {
        $this->quoteResource->load(
            $this->quote,
            'test_order_1',
            'reserved_order_id'
        );
        $sku = 'gift-card-with-amount';
        $product = $this->productRepository->get($sku);
        /** @var Amount $giftcardAmount */
        $giftcardAmount = $product->getExtensionAttributes()->getGiftcardAmounts()[0];
        $giftcardOptionUid = $this->getUidByValue((int) $giftcardAmount->getValue());
        $maskedQuoteId = $this->quoteIdToMaskedId->execute((int) $this->quote->getId());
        $query = $this->getInvalidQueryAddProductsToCart(
            $maskedQuoteId,
            $sku,
            (string) $giftcardOptionUid
        );
        $response = $this->graphQlMutation($query);

        self::assertEquals(
            "Please specify all the required information.",
            $response['addProductsToCart']['user_errors'][0]['message']
        );
    }

    /**
     * Get Uid by value
     *
     * @param int $value
     *
     * @return string
     */
    private function getUidByValue(int $value): string
    {
        $value = number_format($value, 4, '.', '');
        return base64_encode('giftcard_amount/' . $value);
    }

    private function getQueryProducts(string $sku): string
    {
        return <<<QUERY
{
  products(filter: {sku: {eq: "{$sku}"}}) {
    items {
      sku
      ... on GiftCardProduct {
        allow_open_amount
        open_amount_min
        open_amount_max
        giftcard_type
        is_redeemable
        lifetime
        allow_message
        message_max_length
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
     * Get the valid query for addProductsToCart mutation
     *
     * @param string $maskedQuoteId
     * @param string $sku
     * @param string $giftcardOptionUid
     * @param string $senderName
     * @param string $senderEmail
     * @param string $recipientName
     * @param string $recipientEmail
     * @param string $message
     * @param array $uidMap
     *
     * @return string
     */
    private function getValidQueryAddProductsToCart(
        string $maskedQuoteId,
        string $sku,
        string $giftcardOptionUid,
        string $senderName,
        string $senderEmail,
        string $recipientName,
        string $recipientEmail,
        string $message,
        array $uidMap
    ): string {
        return <<<QUERY
mutation {
  addProductsToCart(
    cartId: "{$maskedQuoteId}",
    cartItems: [
      {
        sku: "{$sku}"
        quantity: 1
        selected_options: [
          "{$giftcardOptionUid}"
        ]
        entered_options: [{
          uid: "{$uidMap['Sender Name']}"
          value: "{$senderName}"
        }, {
          uid: "{$uidMap['Sender Email']}"
          value: "{$senderEmail}"
      	}, {
      	  uid: "{$uidMap['Recipient Name']}"
          value: "{$recipientName}"
      	}, {
          uid: "{$uidMap['Recipient Email']}"
          value: "{$recipientEmail}"
        }, {
      	  uid: "{$uidMap['Message']}"
          value: "{$message}"
      	}]
      }
    ]
  ) {
    cart {
      items {
        id
        quantity
        product {
          sku
        }
        ... on GiftCardCartItem {
          sender_name
          sender_email
          recipient_name
          recipient_email
          message
          amount {
            value
            currency
          }
        }
      }
    }
  }
}
QUERY;
    }

    /**
     * Get the valid query for addProductsToCart mutation with custom giftcard amount
     *
     * @param string $maskedQuoteId
     * @param string $sku
     * @param string $customAmountValue
     * @param string $senderName
     * @param string $senderEmail
     * @param string $recipientName
     * @param string $recipientEmail
     * @param string $message
     * @param array $uidMap
     *
     * @return string
     */
    private function getValidQueryAddProductsToCartWithCustomAmount(
        string $maskedQuoteId,
        string $sku,
        string $customAmountValue,
        string $senderName,
        string $senderEmail,
        string $recipientName,
        string $recipientEmail,
        string $message,
        array $uidMap
    ): string {
        return <<<QUERY
mutation {
  addProductsToCart(
    cartId: "{$maskedQuoteId}",
    cartItems: [
      {
        sku: "{$sku}"
        quantity: 1
        entered_options: [{
          uid: "{$uidMap['Custom Giftcard Amount']}"
      	  value: "{$customAmountValue}"
        }, {
          uid: "{$uidMap['Sender Name']}"
          value: "{$senderName}"
        }, {
          uid: "{$uidMap['Sender Email']}"
          value: "{$senderEmail}"
      	}, {
      	  uid: "{$uidMap['Recipient Name']}"
          value: "{$recipientName}"
      	}, {
          uid: "{$uidMap['Recipient Email']}"
          value: "{$recipientEmail}"
        }, {
      	  uid: "{$uidMap['Message']}"
          value: "{$message}"
      	}]
      }
    ]
  ) {
    cart {
      items {
        id
        quantity
        product {
          sku
        }
        ... on GiftCardCartItem {
          sender_name
          sender_email
          recipient_name
          recipient_email
          message
          amount {
            value
            currency
          }
        }
      }
    }
  }
}
QUERY;
    }

    /**
     * Get the invalid query for addProductsToCart mutation
     *
     * @param string $maskedQuoteId
     * @param string $sku
     * @param string $giftcardOptionUid
     *
     * @return string
     */
    private function getInvalidQueryAddProductsToCart(
        string $maskedQuoteId,
        string $sku,
        string $giftcardOptionUid
    ): string {
        return <<<QUERY
mutation {
  addProductsToCart(
    cartId: "{$maskedQuoteId}",
    cartItems: [
      {
        sku: "{$sku}"
        quantity: 1
        selected_options: [
          "{$giftcardOptionUid}"
        ]
      }
    ]
  ) {
    cart {
      items {
        id
        quantity
        product {
          sku
        }
        ... on GiftCardCartItem {
          sender_name
          sender_email
          recipient_name
          recipient_email
          message
          amount {
            value
            currency
          }
        }
      }
    }
    user_errors {
      message
    }
  }
}
QUERY;
    }

    /**
     * Giftcard options (sender_name, recipient_name, etc) to add giftcard to cart
     *
     * @return array
     */
    private function giftcardOptionDataProvider(): array
    {
        return [
            [
                'giftcard_sender_name' => 'Sender 1',
                'giftcard_sender_email' => 'sender1@email.com',
                'giftcard_recipient_name' => 'Recipient 1',
                'giftcard_recipient_email' => 'recipient1@email.com',
                'giftcard_message' => 'Message 1',
            ],
            [
                'giftcard_sender_name' => 'Sender 2',
                'giftcard_sender_email' => 'sender2@email.com',
                'giftcard_recipient_name' => 'Recipient 2',
                'giftcard_recipient_email' => 'recipient2@email.com',
                'giftcard_message' => 'Message 2',
            ]
        ];
    }

    /**
     * Iterable for creating uid array map
     *
     * @param array $giftCardOptions
     * @return array
     */
    public function mapUidsToGiftCardOptions($giftCardOptions)
    {
        $uidMap = [];
        foreach ($giftCardOptions as $giftCardOption) {
            $uidMap[$giftCardOption['title']] = $giftCardOption['value']['uid'];
        }
        return $uidMap;
    }
}
