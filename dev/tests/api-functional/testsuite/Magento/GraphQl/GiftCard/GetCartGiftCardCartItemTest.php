<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\GiftCard;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\GraphQl\Quote\GetMaskedQuoteIdByReservedOrderId;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test for getting giftcard cart item from cart query
 */
class GetCartGiftCardCartItemTest extends GraphQlAbstract
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
     * @magentoApiDataFixture Magento/GiftCard/_files/gift_card_1.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/guest/create_empty_cart.php
     * @magentoApiDataFixture Magento/Quote/_files/add_giftcard_product.php
     */
    public function testCartQueryGiftCardItem()
    {
        $query = $this->prepareQuery();
        $response = $this->graphQlQuery($query);

        self::assertArrayHasKey('cart', $response);
        $cartItem = current($response['cart']['items']);
        self::assertArrayHasKey('product', $cartItem);
        self::assertEquals('gift-card-with-amount', $cartItem['product']['sku']);
        self::assertArrayHasKey('sender_name', $cartItem);
        self::assertEquals('Sender', $cartItem['sender_name']);
        self::assertArrayHasKey('sender_email', $cartItem);
        self::assertEquals('sender@email.com', $cartItem['sender_email']);
        self::assertArrayHasKey('recipient_name', $cartItem);
        self::assertEquals('Recipient', $cartItem['recipient_name']);
        self::assertArrayHasKey('recipient_email', $cartItem);
        self::assertEquals('recipient@email.com', $cartItem['recipient_email']);
        self::assertArrayHasKey('message', $cartItem);
        self::assertEquals('Message', $cartItem['message']);
        self::assertArrayHasKey('amount', $cartItem);
        self::assertEquals(7, $cartItem['amount']['value']);
    }

    /**
     * @magentoApiDataFixture Magento/GiftCard/_files/gift_card_1.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/guest/create_empty_cart.php
     * @magentoApiDataFixture Magento/Quote/_files/add_giftcard_product_wo_message.php
     */
    public function testCartQueryGiftCardItemWithoutMessage()
    {
        $query = $this->prepareQuery();
        $response = $this->graphQlQuery($query);

        self::assertArrayHasKey('cart', $response);
        $cartItem = current($response['cart']['items']);
        self::assertArrayHasKey('product', $cartItem);
        self::assertEquals('gift-card-with-amount', $cartItem['product']['sku']);
        self::assertArrayHasKey('sender_name', $cartItem);
        self::assertEquals('Sender', $cartItem['sender_name']);
        self::assertArrayHasKey('sender_email', $cartItem);
        self::assertEquals('sender@email.com', $cartItem['sender_email']);
        self::assertArrayHasKey('recipient_name', $cartItem);
        self::assertEquals('Recipient', $cartItem['recipient_name']);
        self::assertArrayHasKey('recipient_email', $cartItem);
        self::assertEquals('recipient@email.com', $cartItem['recipient_email']);
        self::assertArrayHasKey('message', $cartItem);
        self::assertNull($cartItem['message']);
        self::assertArrayHasKey('amount', $cartItem);
        self::assertEquals(17, $cartItem['amount']['value']);
    }

    /**
     * Prepare query for sending
     *
     * @return string
     * @throws NoSuchEntityException
     */
    private function prepareQuery(): string
    {
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('test_quote');
        return $this->getGraphQlQuery($maskedQuoteId);
    }

    /**
     * Create cart query
     *
     * @param string $maskedQuoteId
     * @return string
     */
    private function getGraphQlQuery(string $maskedQuoteId): string
    {
        return <<<QUERY
{
  cart(cart_id: "{$maskedQuoteId}") {
    items {
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
QUERY;
    }
}
