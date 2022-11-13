<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\GiftWrapping\Cart;

use Magento\GiftWrapping\Model\ResourceModel\Wrapping as GiftWrappingResource;
use Magento\GiftWrapping\Model\Wrapping as GiftWrapping;
use Magento\GraphQl\Quote\GetMaskedQuoteIdByReservedOrderId;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

class SetGiftOptionsOnCartTest extends GraphQlAbstract
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
     * @magentoConfigFixture default_store sales/gift_options/allow_order 1
     * @magentoConfigFixture default_store sales/gift_options/wrapping_allow_order 1
     * @magentoConfigFixture default_store sales/gift_options/allow_gift_receipt 1
     * @magentoConfigFixture default_store sales/gift_options/allow_printed_card 1
     * @magentoApiDataFixture Magento/GiftWrapping/_files/quote/quote_with_selected_gift_wrapping.php
     */
    public function testSetGiftOptionsQuery()
    {
        $quoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('test_quote_with_selected_gift_wrapping');

        $allGiftWrappings = <<<QUERY
{
   cart(cart_id: "$quoteId") {
    available_gift_wrappings  {
        id
        uid
    }
  }
}
QUERY;

        $response = $this->graphQlQuery($allGiftWrappings);
        $this->assertArrayHasKey(2, $response['cart']['available_gift_wrappings']);
        $giftWrappingId = $response['cart']['available_gift_wrappings'][2]['id'];
        $this->assertSame(
            $response['cart']['available_gift_wrappings'][2]['uid'],
            $response['cart']['available_gift_wrappings'][2]['id']
        );

        $query =  <<<QUERY
mutation{
  setGiftOptionsOnCart(input: {
    cart_id: "$quoteId",
    gift_message: {
      to: "Alex"
      from: "Jon"
      message: "Good job"
    },
    gift_wrapping_id:  "{$giftWrappingId}",
    gift_receipt_included:  true
    printed_card_included: true
 })
  {
    cart {
      id
      gift_message {
        to
        from
        message
      }
      gift_wrapping {
        id
        uid
      }
      gift_receipt_included
      printed_card_included
    }
  }
}
QUERY;
        $result = $this->graphQlMutation($query);
        $this->assertArrayHasKey('gift_wrapping', $result['setGiftOptionsOnCart']['cart']);
        $giftOptionsResponse = $result['setGiftOptionsOnCart']['cart'];
        $this->assertSame('Alex', $giftOptionsResponse['gift_message']['to']);
        $this->assertSame('Jon', $giftOptionsResponse['gift_message']['from']);
        $this->assertSame('Good job', $giftOptionsResponse['gift_message']['message']);
        $this->assertTrue($giftOptionsResponse['gift_receipt_included']);
        $this->assertTrue($giftOptionsResponse['printed_card_included']);

        $query =  <<<QUERY
mutation{
 setGiftOptionsOnCart(input: {
    cart_id: "$quoteId",
    gift_message: {
      to: ""
      from: ""
      message: ""
    },
    gift_wrapping_id:  null,
    gift_receipt_included: false
    printed_card_included: false
 })
  {
    cart {
      id
      gift_message {
        to
        from
        message
      }
      gift_wrapping {
        id
        uid
      }
      gift_receipt_included
      printed_card_included
    }
  }
}
QUERY;
        $resultWithGiftWrappingIdNull = $this->graphQlMutation($query);
        $this->assertArrayHasKey('gift_wrapping', $result['setGiftOptionsOnCart']['cart']);
        $this->assertSame(null, $resultWithGiftWrappingIdNull['setGiftOptionsOnCart']['cart']['gift_wrapping']);
        $giftOptionsResponse = $resultWithGiftWrappingIdNull['setGiftOptionsOnCart']['cart'];
        $this->assertSame('', $giftOptionsResponse['gift_message']['to']);
        $this->assertSame('', $giftOptionsResponse['gift_message']['from']);
        $this->assertSame('', $giftOptionsResponse['gift_message']['message']);
        $this->assertFalse($giftOptionsResponse['gift_receipt_included']);
        $this->assertFalse($giftOptionsResponse['printed_card_included']);
    }
}
