<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GraphQl\GiftCard;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\GiftCard\Api\Data\GiftcardAmountInterface;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\TestCase\GraphQlAbstract;

class GiftCardProductViewTest extends GraphQlAbstract
{

    /**
     * @magentoApiDataFixture Magento/GiftCard/_files/gift_card_1.php
     */
    public function testAllFieldsGiftCardProduct()
    {
        $productSku = 'gift-card-with-amount';
        $query
            = <<<QUERY
{
   products(filter: {sku: {eq: "{$productSku}"}})
   {
       items{
           id           
           type_id
           name
           sku
           ... on PhysicalProductInterface {
             weight
           }
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
            giftcard_amounts{
              value_id
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

        $response = $this->graphQlQuery($query);
        $this->assertArrayHasKey('products', $response);
        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = ObjectManager::getInstance()->get(ProductRepositoryInterface::class);
        $giftCardProduct = $productRepository->get($productSku, false, null, true);
        $this->assertGiftcardBaseField($giftCardProduct, $response['products']['items'][0]);

        /** @var GiftcardAmountInterface $giftcardAmounts */
        $giftcardAmounts = $giftCardProduct->getGiftcardAmounts();
        $this->assertGiftcardAmounts($giftcardAmounts, $response['products']['items'][0]);
    }

    /**
     * @param ProductInterface $product
     * @param array            $actualResponse
     */
    private function assertGiftcardBaseField($product, $actualResponse)
    {
        $assertionMap = [
                ['response_field' => 'sku', 'expected_value' => $product->getSku()],
                ['response_field' => 'type_id', 'expected_value' => $product->getTypeId()],
                ['response_field' => 'id', 'expected_value' => $product->getId()],
                ['response_field' => 'name', 'expected_value' => $product->getName()],
                ['response_field' => 'gift_message_available', 'expected_value' => $product->getGiftMessageAvailable()],
                ['response_field' => 'allow_message', 'expected_value' => (bool)$product->getAllowMessage()],
                ['response_field' => 'allow_open_amount', 'expected_value' => (bool)$product->getAllowOpenAmount()],
                ['response_field' => 'is_redeemable', 'expected_value' => (bool)$product->getIsRedeemable()],
                ['response_field' => 'open_amount_min', 'expected_value' => $product->getOpenAmountMin()],
                ['response_field' => 'open_amount_max', 'expected_value' => $product->getOpenAmountMax()],

            ];
        $this->assertResponseFields($actualResponse, $assertionMap);
        $this->assertNull($actualResponse['weight']);
        if (!(bool)$product->getGiftcardType()) {
            $this->assertEquals('VIRTUAL', $actualResponse['giftcard_type']);
        }
    }

    /**
     * @param GiftcardAmountInterface $giftcardAmount
     * @param array $actualResponse
     */
    private function assertGiftcardAmounts($giftcardAmount, $actualResponse)
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
                        'website_id'    => $giftcardAmount[$index]['website_id'],
                        'attribute_id'  => $giftcardAmount[$index]['attribute_id'],
                        'value'         => $giftcardAmount[$index]['value'],
                        'website_value' => $giftcardAmount[$index]['website_value'],
                        'value_id'      => $giftcardAmount[$index]['value_id']
                    ]
            );
        }
    }
}
