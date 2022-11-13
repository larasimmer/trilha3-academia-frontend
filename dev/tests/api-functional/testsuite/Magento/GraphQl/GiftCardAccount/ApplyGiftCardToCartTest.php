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
 * Test Apply Giftcard to Cart functionality for guest
 */
class ApplyGiftCardToCartTest extends GraphQlAbstract
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
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/guest/create_empty_cart.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     * @magentoApiDataFixture Magento/GiftCardAccount/_files/giftcardaccount.php
     */
    public function testApplyGiftCardToCart()
    {
        $giftCardCode ='giftcardaccount_fixture';
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('test_quote');
        $query = $this->getQuery($maskedQuoteId, $giftCardCode);
        $response = $this->graphQlMutation($query);

        self::assertArrayHasKey('applyGiftCardToCart', $response);
        $prices = $response['applyGiftCardToCart']['cart']['prices'];
        $appliedGiftCards = $response['applyGiftCardToCart']['cart']['applied_gift_cards'];
        $expirationDate = date('Y-m-d', strtotime('+1 week'));
        self::assertEquals($giftCardCode, $appliedGiftCards[0]['code']);
        self::assertNotEmpty($appliedGiftCards[0]['applied_balance'], "Failed: 'applied_balance' must not be empty");

        self::assertNotNull($appliedGiftCards[0]['expiration_date']);
        self::assertEquals($expirationDate, $appliedGiftCards[0]['expiration_date']);

        self::assertEquals('USD', $appliedGiftCards[0]['applied_balance']['currency']);
        self::assertEquals(9.99, $appliedGiftCards[0]['applied_balance']['value']);

        self::assertEquals('USD', $appliedGiftCards[0]['current_balance']['currency']);
        self::assertEquals(9.99, $appliedGiftCards[0]['current_balance']['value']);

        self::assertEquals('USD', $prices['grand_total']['currency']);
        self::assertEquals('USD', $prices['subtotal_excluding_tax']['currency']);
        self::assertEquals('USD', $prices['subtotal_with_discount_excluding_tax']['currency']);
        self::assertEquals(10.01, $prices['grand_total']['value']);
        self::assertEquals(20, $prices['subtotal_excluding_tax']['value']);
        self::assertEquals(20, $prices['subtotal_with_discount_excluding_tax']['value']);
    }

    /**
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/guest/create_empty_cart.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     * @magentoApiDataFixture Magento/GiftCardAccount/_files/giftcardaccount.php
     * @magentoApiDataFixture Magento/Directory/_files/usd_cny_rate.php
     * @magentoConfigFixture default_store currency/options/allow CNY,USD
     */
    public function testApplyGiftCardToCartWithCurrencyHeader()
    {
        $giftCardCode ='giftcardaccount_fixture';
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('test_quote');
        $query = $this->getQuery($maskedQuoteId, $giftCardCode);
        $headerMap = [
            'Content-Currency' => 'CNY'
        ];
        $response = $this->graphQlMutation($query, [], '', $headerMap);

        self::assertArrayHasKey('applyGiftCardToCart', $response);
        $prices = $response['applyGiftCardToCart']['cart']['prices'];
        $appliedGiftCards = $response['applyGiftCardToCart']['cart']['applied_gift_cards'];
        $expirationDate = date('Y-m-d', strtotime('+1 week'));
        self::assertEquals($giftCardCode, $appliedGiftCards[0]['code']);
        self::assertNotEmpty($appliedGiftCards[0]['applied_balance'], "Failed: 'applied_balance' must not be empty");

        self::assertNotNull($appliedGiftCards[0]['expiration_date']);
        self::assertEquals($expirationDate, $appliedGiftCards[0]['expiration_date']);

        self::assertEquals('CNY', $appliedGiftCards[0]['applied_balance']['currency']);
        self::assertEquals(69.93, $appliedGiftCards[0]['applied_balance']['value']);

        self::assertEquals('CNY', $appliedGiftCards[0]['current_balance']['currency']);
        self::assertEquals(69.93, $appliedGiftCards[0]['current_balance']['value']);

        self::assertEquals('CNY', $prices['grand_total']['currency']);
        self::assertEquals('CNY', $prices['subtotal_excluding_tax']['currency']);
        self::assertEquals('CNY', $prices['subtotal_with_discount_excluding_tax']['currency']);
        self::assertEquals(70.07, $prices['grand_total']['value']);
        self::assertEquals(140, $prices['subtotal_excluding_tax']['value']);
        self::assertEquals(140, $prices['subtotal_with_discount_excluding_tax']['value']);
    }

    /**
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/guest/create_empty_cart.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     * @magentoApiDataFixture Magento/GiftCardAccount/_files/giftcardaccount.php
     */
    public function testApplySameGiftCardTwice()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The gift card code couldn\'t be added. Verify your information and try again.');

        $giftCardCode ='giftcardaccount_fixture';
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('test_quote');
        $query = $this->getQuery($maskedQuoteId, $giftCardCode);
        $response = $this->graphQlMutation($query);

        self::assertArrayHasKey("applyGiftCardToCart", $response);
        self::assertEquals($giftCardCode, $response['applyGiftCardToCart']['cart']['applied_gift_cards'][0]['code']);

        $this->graphQlMutation($query);
    }

    /**
     * Apply multiple gift cards where the first giftcard balance < cart total and
     *
     * hence should be able to successfully apply a different gift card
     *
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/guest/create_empty_cart.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     * @magentoApiDataFixture Magento/GiftCardAccount/_files/giftcardaccounts_for_search.php
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function testApplyMultipleGiftCardsToTheCart()
    {
        $firstGiftCardCode ='gift_card_account_1';
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('test_quote');
        $query = $this->getQuery($maskedQuoteId, $firstGiftCardCode);
        $response = $this->graphQlMutation($query);

        self::assertArrayHasKey("applyGiftCardToCart", $response);
        self::assertEquals(
            $firstGiftCardCode,
            $response['applyGiftCardToCart']['cart']['applied_gift_cards'][0]['code']
        );
        $secondGiftCardCode = 'gift_card_account_5';
        $query = $this->getQuery($maskedQuoteId, $secondGiftCardCode);

        $response = $this->graphQlMutation($query);
        $expectedAppliedGiftCards =['gift_card_account_1', 'gift_card_account_5'];
        $appliedGiftCardCodesFromResponse = $response['applyGiftCardToCart']['cart']['applied_gift_cards'];
        foreach ($appliedGiftCardCodesFromResponse as $index => $codes) {
            $this->assertEquals($expectedAppliedGiftCards[$index], $appliedGiftCardCodesFromResponse[$index]['code']);
        }
        $expirationDate = date('Y-m-d', strtotime('+1 week'));
        foreach ($appliedGiftCardCodesFromResponse as $index => $giftCard) {
            $this->assertEquals($expirationDate, $appliedGiftCardCodesFromResponse[$index]['expiration_date']);
        }

        $totalActualAppliedValue = 0;
        foreach ($appliedGiftCardCodesFromResponse as $index => $giftCards) {
            $totalActualAppliedValue =
                $totalActualAppliedValue + $appliedGiftCardCodesFromResponse[$index]['applied_balance']['value'];
        }
        $this->assertEquals(20, $totalActualAppliedValue);

        $totalActualRemainingBalance = 0;
        foreach ($appliedGiftCardCodesFromResponse as $index => $giftCards) {
            $totalActualRemainingBalance =
                $totalActualRemainingBalance + $appliedGiftCardCodesFromResponse[$index]['current_balance']['value'];
        }
        $this->assertEquals(60, $totalActualRemainingBalance);
    }

    /**
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/guest/create_empty_cart.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     * @magentoApiDataFixture Magento/GiftCardAccount/_files/giftcardaccount_with_zero_balance.php
     */
    public function testApplyGiftCardWithZeroBalance()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The gift card code couldn\'t be added. Verify your information and try again.');

        $giftCardCodeWithZeroBalance = 'giftcardaccount_with_zero_balance';
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('test_quote');
        $query = $this->getQuery($maskedQuoteId, $giftCardCodeWithZeroBalance);
        $this->graphQlMutation($query);
    }

    /**
     * Apply multiple gift cards where the order totals are fully paid by first gift card and
     * verifies that a second card cannot be applied on the cart
     *
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/guest/create_empty_cart.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     * @magentoApiDataFixture Magento/GiftCardAccount/_files/giftcardaccounts_for_search.php
     * @expectedExceptionMessage The gift card code couldn't be added. Verify your information and try again.
     */
    public function testApplySecondGiftCardAfterCartTotalIsZero()
    {
        $firstGiftCardCode ='gift_card_account_5';
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('test_quote');
        $query = $this->getQuery($maskedQuoteId, $firstGiftCardCode);
        $response = $this->graphQlMutation($query);

        self::assertArrayHasKey("applyGiftCardToCart", $response);
        self::assertEquals(
            $firstGiftCardCode,
            $response['applyGiftCardToCart']['cart']['applied_gift_cards'][0]['code']
        );
        $secondGiftCardCode = 'gift_card_account_2';
        $query = $this->getQuery($maskedQuoteId, $secondGiftCardCode);
        $this->graphQlMutation($query);
    }

    /**
     * Apply expired gift card to the cart
     *
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/guest/create_empty_cart.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     * @magentoApiDataFixture Magento/GiftCardAccount/_files/giftcardaccount.php
     * @magentoApiDataFixture Magento/GiftCardAccount/_files/expired_giftcard_account.php
     *
     * @expectedExceptionMessage The gift card code couldn't be added. Verify your information and try again.
     */
    public function testApplyExpiredGiftCard()
    {
        $giftCardCode ='giftcardaccount_fixture';
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('test_quote');
        $query = $this->getQuery($maskedQuoteId, $giftCardCode);
        $this->graphQlMutation($query);
    }

    /**
     * @param string $maskedQuoteId
     * @param string $giftCardCode
     * @return string
     */
    private function getQuery(string $maskedQuoteId, string $giftCardCode): string
    {
        return <<<QUERY
mutation {
  applyGiftCardToCart(input: {cart_id: "$maskedQuoteId", gift_card_code: "$giftCardCode"}) {
    cart {
      prices {
        grand_total {
          currency
          value
        }
        subtotal_excluding_tax {
          currency
          value
        }
        subtotal_with_discount_excluding_tax {
          currency
          value
        }
      }
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
}
