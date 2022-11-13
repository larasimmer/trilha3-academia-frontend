<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\GiftWrapping\Order;

use Magento\Framework\Exception\AuthenticationException;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

class GiftWrappingTest extends GraphQlAbstract
{
    /**
     * @var CustomerTokenServiceInterface
     */
    private $customerTokenService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customerTokenService = Bootstrap::getObjectManager()->get(CustomerTokenServiceInterface::class);
    }

    /**
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/GiftWrapping/_files/customer/order_with_gift_wrapping.php
     */
    public function testGiftWrappingForOrder()
    {
        $query = $this->getCustomerOrdersQuery();
        $currentEmail = 'customer@example.com';
        $currentPassword = 'password';
        $result = $this->graphQlQuery($query, [], '', $this->getCustomerAuthHeaders($currentEmail, $currentPassword));
        $product = $result["customer"]["orders"]["items"][0];
        $this->assertSame('999999990', $product['number']);
        $this->assertTrue($product['gift_receipt_included']);
        $this->assertTrue($product['gift_receipt_included']);
        $this->assertSame('Test Wrapping 1', $product['gift_wrapping']['design']);
        $this->assertSame('image1.png', $product['gift_wrapping']['image']['label']);
        $giftWrappingItem = $result["customer"]["orders"]["items"][0]["items"][0];
        $this->assertSame('Test Wrapping 2 design', $giftWrappingItem['gift_wrapping']['design']);
        $this->assertSame('image2.png', $giftWrappingItem['gift_wrapping']['image']['label']);
    }

    /**
     * Get Customer Orders query
     *
     * @return string
     */
    private function getCustomerOrdersQuery()
    {
        return <<<QUERY
{
     customer {
       orders(filter:{number:{eq:"999999990"}}) {
       items {
       id
        number
      status
      printed_card_included
      gift_receipt_included
      gift_wrapping {
        id
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
        items{
          gift_wrapping{
            id
            design
            image
            {
              label
              url
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
     * @param string $email
     * @param string $password
     *
     * @return array
     *
     * @throws AuthenticationException
     */
    private function getCustomerAuthHeaders(string $email, string $password): array
    {
        $customerToken = $this->customerTokenService->createCustomerAccessToken($email, $password);
        return ['Authorization' => 'Bearer ' . $customerToken];
    }
}
