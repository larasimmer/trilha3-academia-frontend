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
 * Test to check removing of the applied gift card from the cart
 */
class RemoveGiftCardFromCartTest extends GraphQlAbstract
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
     * Test to remove the applied gift card from the cart
     *
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/guest/create_empty_cart.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     * @magentoApiDataFixture Magento/GiftCardAccount/_files/giftcardaccount.php
     */
    public function testRemoveAppliedGiftCardFromCart()
    {
        $giftCardCode ='giftcardaccount_fixture';
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('test_quote');

        $applyQuery = $this->applyGiftCardToCartQuery($maskedQuoteId, $giftCardCode);
        $this->graphQlMutation($applyQuery);

        $removeQuery = $this->getRemoveQuery($maskedQuoteId, $giftCardCode);
        $response = $this->graphQlMutation($removeQuery);

        self::assertArrayHasKey('removeGiftCardFromCart', $response);
        self::assertEmpty($response['removeGiftCardFromCart']['cart']['applied_gift_cards']);
    }

    /**
     * Test to apply multiple gift cards to the cart and then remove them from the cart one by one.
     *
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/guest/create_empty_cart.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     * @magentoApiDataFixture Magento/GiftCardAccount/_files/giftcardaccounts_for_search.php
     */
    public function testRemoveAppliedMultipleGiftCardsFromTheCartOneByOne()
    {
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('test_quote');

        $firstGiftCardCode ='gift_card_account_1';
        $applyQuery = $this->applyGiftCardToCartQuery($maskedQuoteId, $firstGiftCardCode);
        $this->graphQlMutation($applyQuery);

        $secondGiftCardCode = 'gift_card_account_2';
        $applyQuery = $this->applyGiftCardToCartQuery($maskedQuoteId, $secondGiftCardCode);
        $this->graphQlMutation($applyQuery);

        $removeQuery = $this->getRemoveQuery($maskedQuoteId, $secondGiftCardCode);
        $response = $this->graphQlMutation($removeQuery);
        self::assertEquals(
            $firstGiftCardCode,
            $response['removeGiftCardFromCart']['cart']['applied_gift_cards'][0]['code']
        );

        $removeQuery = $this->getRemoveQuery($maskedQuoteId, $firstGiftCardCode);
        $response = $this->graphQlMutation($removeQuery);
        self::assertArrayHasKey('removeGiftCardFromCart', $response);
        self::assertEmpty($response['removeGiftCardFromCart']['cart']['applied_gift_cards']);
    }

    /**
     * Test to remove the non existing gift card from the cart
     *
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/guest/create_empty_cart.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     * @magentoApiDataFixture Magento/GiftCardAccount/_files/giftcardaccount.php
     */
    public function testRemoveNonExistingGiftCardFromCart()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The gift card couldn\'t be deleted from the quote.');

        $giftCardCode ='giftcardaccount_fixture';
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('test_quote');
        $applyQuery = $this->applyGiftCardToCartQuery($maskedQuoteId, $giftCardCode);
        $this->graphQlMutation($applyQuery);

        $nonExistingGiftCardCode ='giftcardaccount' ;
        $removeQuery = $this->getRemoveQuery($maskedQuoteId, $nonExistingGiftCardCode);
        $this->graphQlMutation($removeQuery);
    }

    /**
     * Mutation to Remove gift card from the cart.
     *
     * @param string $maskedQuoteId
     * @param string $giftCardCode
     * @return string
     */
    private function getRemoveQuery(string $maskedQuoteId, string $giftCardCode): string
    {
        return <<<QUERY
mutation {
  removeGiftCardFromCart(input: {
    cart_id: "$maskedQuoteId"
    gift_card_code: "$giftCardCode" }) {
    cart {
      applied_gift_cards {
        code
      }
    }
  }
}
QUERY;
    }

    /**
     * Mutation for applying the gift card to the cart.
     *
     * @param string $maskedQuoteId
     * @param string $giftCardCode
     * @return string
     */
    private function applyGiftCardToCartQuery(string $maskedQuoteId, string $giftCardCode): string
    {
        return <<<QUERY
mutation {
  applyGiftCardToCart(input: {
    cart_id: "$maskedQuoteId"
    gift_card_code: "$giftCardCode" }) {
    cart {
      applied_gift_cards {
        code
      }
    }
  }
}
QUERY;
    }
}
