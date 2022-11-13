<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\GiftWrapping\Cart;

use Exception;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\GraphQl\Quote\GetMaskedQuoteIdByReservedOrderId;
use Magento\TestFramework\Helper\Bootstrap;

class GiftWrappingTest extends GraphQlAbstract
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
     * @magentoApiDataFixture Magento/GiftWrapping/_files/quote/quote_with_selected_gift_wrapping.php
     */
    public function testSelectedGiftWrappingForCart()
    {
        $response = $this->requestCartQuery();
        $this->assertArrayHasKey('gift_wrapping', $response['cart']);
        $giftWrapping = $response['cart']['gift_wrapping'];
        $this->assertSame('Test Wrapping 1', $giftWrapping['design']);
        $this->assertSame(base64_encode($giftWrapping['id']), $giftWrapping['uid']);
        $this->assertSame('image1.png', $giftWrapping['image']['label']);
        $this->assertSame(5, $giftWrapping['price']['value']);
    }

    /**
     * @magentoConfigFixture default_store sales/gift_options/wrapping_allow_order 1
     * @magentoApiDataFixture Magento/GiftWrapping/_files/quote/quote_with_selected_gift_wrapping.php
     */
    public function testGetAvailableGiftWrappingsForCart()
    {
        $availableGiftWrappings = [
            [
                'design'=>'Test Wrapping 1',
                'image'=>[
                    'label' => 'image1.png'
                ],
                'price'=>[
                    'value' => 5
                ]
            ],
            [
                'design'=>'Test Wrapping 2 design',
                'image'=>[
                    'label' => 'image2.png'
                ],
                'price'=>[
                    'value' => 10
                ]
            ],
            [
                'design'=>'Test Wrapping 3',
                'image'=>[
                    'label' => 'image3.png'
                ],
                'price'=>[
                    'value' => 15
                ]
            ],
        ];
        $response = $this->requestCartQuery();
        $this->assertArrayHasKey('gift_wrapping', $response['cart']);
        $this->assertArrayHasKey('available_gift_wrappings', $response['cart']);
        $giftWrappings = $response['cart']['available_gift_wrappings'];
        foreach ($giftWrappings as $key => $value) {
            $this->assertSame($value['id'], $value['uid']);
            $this->assertSame($availableGiftWrappings[$key]['design'], $value['design']);
            $this->assertSame($availableGiftWrappings[$key]['image']['label'], $value['image']['label']);
            $this->assertSame($availableGiftWrappings[$key]['price']['value'], $value['price']['value']);
        }
    }

    /**
     * @magentoConfigFixture default_store sales/gift_options/wrapping_allow_order 0
     * @magentoApiDataFixture Magento/GiftWrapping/_files/quote/quote_with_selected_gift_wrapping.php
     */
    public function testGetNotAllowGiftWrappingsForCart()
    {
        $response = $this->requestCartQuery();
        $this->assertArrayHasKey('gift_wrapping', $response['cart']);
        $this->assertArrayHasKey('available_gift_wrappings', $response['cart']);
        $giftWrappings = $response['cart']['available_gift_wrappings'];
        $this->assertCount(0, $giftWrappings);
    }

    /**
     * @magentoApiDataFixture Magento/GiftWrapping/_files/quote/quote_with_selected_gift_wrapping.php
     */
    public function testGetPrintedCardIncludedForCart()
    {
        $response = $this->requestCartQuery();
        $this->assertArrayHasKey('gift_wrapping', $response['cart']);
        $this->assertArrayHasKey('printed_card_included', $response['cart']);
        $this->assertTrue($response['cart']['printed_card_included']);
    }

    /**
     * @magentoApiDataFixture Magento/GiftWrapping/_files/quote/quote_with_selected_gift_wrapping.php
     */
    public function testGetGiftReceiptIncludedForCart()
    {
        $response = $this->requestCartQuery();
        $this->assertArrayHasKey('gift_wrapping', $response['cart']);
        $this->assertArrayHasKey('gift_receipt_included', $response['cart']);
        $this->assertFalse($response['cart']['gift_receipt_included']);
    }

    /**
     * @magentoConfigFixture default_store sales/gift_options/printed_card_price 99.99
     * @magentoApiDataFixture Magento/GiftWrapping/_files/quote/quote_with_selected_gift_wrapping.php
     */
    public function testGetGiftWrappingPricesForCart()
    {
        $response = $this->requestCartQuery();
        $this->assertArrayHasKey('gift_options', $response['cart']['prices']);
        $giftWrappingPrices = $response['cart']['prices']['gift_options'];
        $this->assertSame(5, $giftWrappingPrices['gift_wrapping_for_order']['value']);
        $this->assertSame('USD', $giftWrappingPrices['gift_wrapping_for_order']['currency']);
        $this->assertSame(10, $giftWrappingPrices['gift_wrapping_for_items']['value']);
        $this->assertSame('USD', $giftWrappingPrices['gift_wrapping_for_items']['currency']);
        $this->assertSame(99.99, $giftWrappingPrices['printed_card']['value']);
        $this->assertSame('USD', $giftWrappingPrices['printed_card']['currency']);
    }

    /**
     * Get cart query
     *
     * @return array|bool|float|int|string
     *
     * @throws NoSuchEntityException
     * @throws Exception
     */
    private function requestCartQuery()
    {
        $quoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('test_quote_with_selected_gift_wrapping');

        $query =  <<<QUERY
{
    cart(cart_id: "$quoteId") {
        printed_card_included
        gift_receipt_included
        available_gift_wrappings  {
            id
            uid
            design
            image {
              label
              url
            }
            price {
              value
              currency
            }
        }
        gift_wrapping {
            id
            uid
            design
            image {
              label
              url
            }
            price {
              value
              currency
            }
        }
        prices {
            gift_options {
                gift_wrapping_for_order {
                  value
                  currency
                }
                gift_wrapping_for_items {
                  value
                  currency
                }
                printed_card {
                  value
                  currency
                }
            }
        }
    }
}
QUERY;
        return $this->graphQlQuery($query);
    }
}
