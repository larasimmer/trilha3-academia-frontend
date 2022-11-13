<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\GiftWrapping\Cart\Item;

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
     * @magentoConfigFixture default_store sales/gift_options/wrapping_allow_items 1
     * @magentoApiDataFixture Magento/GiftWrapping/_files/quote/quote_with_selected_gift_wrapping.php
     */
    public function testGetAvailableGiftWrappingsForItem()
    {
        $availableWrappings = [
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

        foreach ($response['cart']['items'] as $item) {
            self::assertSame('Simple Product', $item['product']['name']);
            foreach ($item['available_gift_wrapping'] as $key => $value) {
                self::assertSame(base64_encode($value['id']), $value['uid']);
                self::assertSame($availableWrappings[$key]['design'], $value['design']);
                self::assertSame($availableWrappings[$key]['price']['value'], $value['price']['value']);
                self::assertSame($availableWrappings[$key]['image']['label'], $value['image']['label']);
            }
            self::assertSame('Test Wrapping 2 design', $item['gift_wrapping']['design']);
            self::assertSame('image2.png', $item['gift_wrapping']['image']['label']);
        }
    }

    /**
     * @magentoConfigFixture default_store sales/gift_options/wrapping_allow_items 0
     * @magentoApiDataFixture Magento/GiftWrapping/_files/quote/quote_with_selected_gift_wrapping.php
     */
    public function testGetAvailableGiftWrappingsForItemNotAllow()
    {
        $response = $this->requestCartQuery();
        foreach ($response['cart']['items'] as $item) {
            self::assertSame('Simple Product', $item['product']['name']);
            self::assertCount(0, $item['available_gift_wrapping']);
        }
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
        items {
            product {
               name
            }
            quantity
        ... on SimpleCartItem {
                available_gift_wrapping  {
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
            }
        }
    }
}
QUERY;
        return $this->graphQlQuery($query);
    }
}
