<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GraphQl\GiftCard;

use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * @inheritdoc
 */
class GiftCardProductMultipleStoreViewTest extends GraphQlAbstract
{
    /**
     * @magentoApiDataFixture Magento/Store/_files/second_website_with_two_stores.php
     * @magentoApiDataFixture Magento/GiftCard/_files/gift_card_with_amount_multiple_websites.php
     * @dataProvider expectedProductDataProvider
     * @param array $expectedProductData
     * @throws \Exception
     */
    public function testAllFieldsGiftCardProduct(array $expectedProductData)
    {
        $secondWebsiteId = ObjectManager::getInstance()->get(StoreManagerInterface::class)
            ->getStore('fixture_second_store')->getWebsiteId();
        $this->setWebsiteIdToExpectedData($expectedProductData['second_store'], $secondWebsiteId);
        $productSku = 'gift-card-with-amount';
        $headerMapFirstStore['Store'] = 'default';
        $headerMapSecondStore['Store'] = 'fixture_second_store';
        $query = $this->getQuery($productSku);
        $responseForFirstWebsite = $this->graphQlQuery($query, [], '', $headerMapFirstStore);
        $responseForSecondWebsite = $this->graphQlQuery($query, [], '', $headerMapSecondStore);
        $this->assertArrayHasKey('products', $responseForFirstWebsite);

        $this->assertGiftcardBaseField(
            $expectedProductData['first_store'],
            $responseForFirstWebsite['products']['items'][0]
        );
        $this->assertGiftcardAmounts(
            $expectedProductData['first_store']['giftcard_amounts'],
            $responseForFirstWebsite['products']['items'][0]
        );

        $this->assertGiftcardBaseField(
            $expectedProductData['second_store'],
            $responseForSecondWebsite['products']['items'][0]
        );
        $this->assertGiftcardAmounts(
            $expectedProductData['second_store']['giftcard_amounts'],
            $responseForSecondWebsite['products']['items'][0]
        );
    }

    /**
     * @param array $product
     * @param array $actualResponse
     */
    private function assertGiftcardBaseField($product, $actualResponse)
    {
        $assertionMap = [
            ['response_field' => 'sku', 'expected_value' => $product['sku']],
            ['response_field' => 'type_id', 'expected_value' => $product['type_id']],
            ['response_field' => 'name', 'expected_value' => $product['name']],
            ['response_field' => 'gift_message_available', 'expected_value' => $product['gift_message_available']],
            ['response_field' => 'allow_message', 'expected_value' => $product['allow_message']],
            ['response_field' => 'allow_open_amount', 'expected_value' => $product['allow_open_amount']],
            ['response_field' => 'is_redeemable', 'expected_value' => $product['is_redeemable']],
            ['response_field' => 'is_returnable', 'expected_value' => $product['is_returnable']],
            ['response_field' => 'open_amount_min', 'expected_value' => $product['open_amount_min']],
            ['response_field' => 'open_amount_max', 'expected_value' => $product['open_amount_max']],
            ['response_field' => 'lifetime', 'expected_value' => $product['lifetime']],
            ['response_field' => 'message_max_length', 'expected_value' => $product['message_max_length']],
            ['response_field' => 'giftcard_type', 'expected_value' => $product['giftcard_type']],

        ];
        $this->assertResponseFields($actualResponse, $assertionMap);
    }

    /**
     * @param array $giftcardAmounts
     * @param array $actualResponse
     */
    private function assertGiftcardAmounts($giftcardAmounts, $actualResponse)
    {
        $this->assertNotEmpty(
            $actualResponse['giftcard_amounts'],
            "Precondition failed: 'gift card amounts' must not be empty"
        );
        foreach ($actualResponse['giftcard_amounts'] as $index => $items) {
            $this->assertNotEmpty($items);
            $this->assertResponseFields(
                $actualResponse['giftcard_amounts'][$index],
                [
                    'value' => $giftcardAmounts[$index]['value'],
                    'website_value' => $giftcardAmounts[$index]['website_value'],
                    'website_id' => $giftcardAmounts[$index]['website_id']
                ]
            );
        }
    }

    public function expectedProductDataProvider()
    {
        return [
            [
                [
                    'first_store' => [
                        'type_id' => 'giftcard',
                        'name' => 'Simple Gift Card',
                        'sku' => 'gift-card-with-amount',
                        'gift_message_available' => 1,
                        'allow_message' => 1,
                        'message_max_length' => 255,
                        'allow_open_amount' => 1,
                        'open_amount_min' => 100,
                        'open_amount_max' => 150,
                        'is_returnable' => 1,
                        'is_redeemable' => false,
                        'giftcard_type' => 'VIRTUAL',
                        'lifetime' => 0,
                        'giftcard_amounts' => [
                            [
                                'website_id' => 0,
                                'value' => 7,
                                'website_value' => 7,
                            ],
                            [
                                'website_id' => 0,
                                'value' => 17,
                                'website_value' => 17,
                            ]
                        ]
                    ],
                    'second_store' => [
                        'type_id' => 'giftcard',
                        'name' => 'Simple Gift Card',
                        'sku' => 'gift-card-with-amount',
                        'gift_message_available' => 1,
                        'allow_message' => 1,
                        'message_max_length' => 255,
                        'allow_open_amount' => 1,
                        'open_amount_min' => 100,
                        'open_amount_max' => 150,
                        'is_returnable' => 1,
                        'is_redeemable' => false,
                        'giftcard_type' => 'VIRTUAL',
                        'lifetime' => 0,
                        'giftcard_amounts' => [
                            [
                                'website_id' => 0,
                                'value' => 7,
                                'website_value' => 7,
                            ],
                            [
                                'website_id' => 0,
                                'value' => 17,
                                'website_value' => 17,
                            ],
                            [
                                'website_id' => null,
                                'value' => 27,
                                'website_value' => 27
                            ],
                            [
                                'website_id' => null,
                                'value' => 37,
                                'website_value' => 37,
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @param array $expectedData
     * @param int $websiteId
     */
    private function setWebsiteIdToExpectedData(array &$expectedData, int $websiteId)
    {
        foreach ($expectedData['giftcard_amounts'] as &$amount) {
            if (null === $amount['website_id']) {
                $amount['website_id'] = $websiteId;
            }
        }
    }

    /**
     * @param string $sku
     * @return string
     */
    private function getQuery(string $sku)
    {
        return <<<QUERY
        {
           products(filter: {sku: {eq: "$sku"}})
           {
               items{
                   id           
                   type_id
                   name
                   sku
                   ... on GiftCardProduct {
                   gift_message_available
                    allow_message
                    message_max_length
                    allow_open_amount
                    open_amount_min
                    open_amount_max
                    is_returnable
                    is_redeemable
                    giftcard_type
                    lifetime
                    giftcard_amounts{
                      website_id
                      value
                      attribute_id
                      website_value              
                    }
                   }
               }
           }   
        }  
QUERY;
    }
}
