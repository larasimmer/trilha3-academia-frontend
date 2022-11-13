<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\GraphQl\GiftCardAccount;

use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\ObjectManagerInterface;
use Magento\GraphQl\GetCustomerAuthenticationHeader;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test gift card query for order
 */
class AppliedGiftCardsTotalBalanceToOrderTest extends GraphQlAbstract
{
    /**
     * @var GetCustomerAuthenticationHeader
     */
    private $customerAuthenticationHeader;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * Set Up
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->customerAuthenticationHeader = $this->objectManager->get(GetCustomerAuthenticationHeader::class);
    }

    /**
     * @magentoConfigFixture default_store sales/gift_options/allow_order 1
     * @magentoConfigFixture default_store sales/gift_options/allow_items 1
     * @magentoApiDataFixture Magento/GiftCardAccount/_files/customer_order_with_gift_card_account.php
     */
    public function testTotalBalanceAppliedGiftCardsToOrder(): void
    {
        $response = $this->getCustomerOrdersQuery('customer@example.com', 'password');

        self::assertNotNull($response['customer']['orders']['items'][0]['total']['total_giftcard']);
        self::assertEquals(
            20,
            $response['customer']['orders']['items'][0]['total']['total_giftcard']['value']
        );
        self::assertEquals(
            'USD',
            $response['customer']['orders']['items'][0]['total']['total_giftcard']['currency']
        );
    }

    /**
     * @magentoConfigFixture default_store sales/gift_options/allow_order 0
     * @magentoConfigFixture default_store sales/gift_options/allow_items 0
     * @magentoApiDataFixture Magento/GiftCard/_files/customer_order_with_gift_card.php
     */
    public function testTotalBalanceNotAppliedGiftCardsToOrder(): void
    {
        $response = $this->getCustomerOrdersQuery('customer@example.com', 'password');

        self::assertNull($response['customer']['orders']['items'][0]['total']['total_giftcard']);
    }

    /**
     * Get Customer Orders query
     *
     * @param string $currentEmail
     * @param string $currentPassword
     * @return array
     * @throws AuthenticationException
     */
    private function getCustomerOrdersQuery(string $currentEmail, string $currentPassword): array
    {
        $query = <<<QUERY
{
  customer {
    orders {
        items {
            total {
                total_giftcard{
                  value
                  currency
                }
            }
        }
    }
  }
}
QUERY;

        return $this->graphQlQuery(
            $query,
            [],
            '',
            $this->customerAuthenticationHeader->execute($currentEmail, $currentPassword)
        );
    }
}
