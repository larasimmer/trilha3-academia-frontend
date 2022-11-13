<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\GiftCard\Options;

use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test for gift card search query
 */
class GiftCardSearchTest extends GraphQlAbstract
{
    /**
     * Test for gift card search query when allow_open_amount is existed
     *
     * @magentoApiDataFixture Magento/GiftCard/_files/gift_card_with_open_amount.php
     */
    public function testGiftCardSearchWithAllowOpenAmountField(): void
    {
        $query = $this->getQuery(true);
        $response = $this->graphQlQuery($query);

        self::assertEquals(true, $response['products']['items'][0]['allow_open_amount']);
        self::assertEquals('GiftCardProduct', $response['products']['items'][0]['__typename']);
        self::assertEquals('Simple Gift Card', $response['products']['items'][0]['name']);
        self::assertEquals('gift-card-with-open-amount', $response['products']['items'][0]['sku']);
        self::assertEquals('VIRTUAL', $response['products']['items'][0]['giftcard_type']);
        $this->assertGiftCardAmounts($response['products']['items'][0]['giftcard_amounts']);
        $this->assertGiftCardOptions($response['products']['items'][0]['gift_card_options'], true);
    }

    /**
     * Test for custom giftcard amount when allow_open_amount is not existed
     *
     * @magentoApiDataFixture Magento/GiftCard/_files/gift_card_with_open_amount.php
     */
    public function testGiftCardSearchWithoutAllowOpenAmountField()
    {
        $query = $this->getQuery(false);
        $response = $this->graphQlQuery($query);

        self::assertEquals('GiftCardProduct', $response['products']['items'][0]['__typename']);
        self::assertEquals('Simple Gift Card', $response['products']['items'][0]['name']);
        self::assertEquals('gift-card-with-open-amount', $response['products']['items'][0]['sku']);
        self::assertEquals('VIRTUAL', $response['products']['items'][0]['giftcard_type']);
        $this->assertGiftCardAmounts($response['products']['items'][0]['giftcard_amounts']);
        $this->assertGiftCardOptions($response['products']['items'][0]['gift_card_options'], true);
    }

    /**
     * Test for gift card search query when allow_open_amount is existed but is not allowed
     *
     * @magentoApiDataFixture Magento/GiftCard/_files/gift_card_physical_with_fixed_amount_10.php
     */
    public function testGiftCardSearchWithFixedAmountAndAllowOpenAmountField()
    {
        $query = $this->getQuery(true);
        $response = $this->graphQlQuery($query);

        self::assertEquals(false, $response['products']['items'][0]['allow_open_amount']);
        self::assertEquals('GiftCardProduct', $response['products']['items'][0]['__typename']);
        self::assertEquals('Gift Card with fixed amount 10', $response['products']['items'][0]['name']);
        self::assertEquals('gift-card-with-fixed-amount-10', $response['products']['items'][0]['sku']);
        self::assertEquals('PHYSICAL', $response['products']['items'][0]['giftcard_type']);
        $this->assertGiftCardAmounts($response['products']['items'][0]['giftcard_amounts']);
        $this->assertGiftCardOptions($response['products']['items'][0]['gift_card_options'], false);
    }

    /**
     * Test for gift card search query when allow_open_amount is not existed and is not allowed
     *
     * @magentoApiDataFixture Magento/GiftCard/_files/gift_card_physical_with_fixed_amount_10.php
     */
    public function testGiftCardSearchWithFixedAmount()
    {
        $query = $this->getQuery(false);
        $response = $this->graphQlQuery($query);

        self::assertEquals('GiftCardProduct', $response['products']['items'][0]['__typename']);
        self::assertEquals('Gift Card with fixed amount 10', $response['products']['items'][0]['name']);
        self::assertEquals('gift-card-with-fixed-amount-10', $response['products']['items'][0]['sku']);
        self::assertEquals('PHYSICAL', $response['products']['items'][0]['giftcard_type']);
        $this->assertGiftCardAmounts($response['products']['items'][0]['giftcard_amounts']);
        $this->assertGiftCardOptions($response['products']['items'][0]['gift_card_options'], false);
    }

    /**
     * Get query for gift card options
     *
     * @param bool $isAllowOpenAmount
     * @return string
     */
    private function getQuery(bool $isAllowOpenAmount): string
    {
        $allowOpenAmount = $isAllowOpenAmount ? 'allow_open_amount' : '';

        return <<<QUERY
{
  products(search: "test gift card", pageSize: 1) {
    items {
      __typename
      name
      sku
      id
      ... on GiftCardProduct {
        giftcard_type
        {$allowOpenAmount}
        giftcard_amounts {
          uid
          value
          value_id
          attribute_id
        }
        options {
          title
          option_id
        }
        gift_card_options {
          title
          option_id
          required
          ... on CustomizableFieldOption {
            value {
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
     * Get Uid by value
     *
     * @param int $value
     *
     * @return string
     */
    private function getUidByValue(int $value): string
    {
        $value = number_format($value, 4, '.', '');
        return base64_encode('giftcard/giftcard_amount/' . $value);
    }

    /**
     * Assert gift card options
     *
     * @param array $giftCardOptions
     */
    private function assertGiftCardOptions(array $giftCardOptions, $isCustomGiftcardAmount): void
    {
        self::assertNotEmpty($giftCardOptions);

        $customGiftcardAmount = false;
        foreach ($giftCardOptions as $giftCardOption) {
            self::assertNotEmpty($giftCardOption['value']);
            self::assertArrayHasKey('uid', $giftCardOption['value']);

            if ($giftCardOption['title'] === 'Custom Giftcard Amount') {
                $customGiftcardAmount = true;
            }
        }

        self::assertEquals($isCustomGiftcardAmount, $customGiftcardAmount);
    }

    /**
     * Assert gidt card amounts
     *
     * @param array $giftcardAmounts
     */
    private function assertGiftCardAmounts(array $giftcardAmounts): void
    {
        self::assertNotEmpty($giftcardAmounts);
        foreach ($giftcardAmounts as $giftcardAmount) {
            $uid = $this->getUidByValue((int)$giftcardAmount['value']);
            self::assertEquals($uid, $giftcardAmount['uid']);
        }
    }
}
