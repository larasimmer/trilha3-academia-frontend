<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\GiftCardAccount;

use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test giftCardAccount query
 */
class GiftCardAccountQueryTest extends GraphQlAbstract
{
    /**
     * Test query returns a correct, successful result
     *
     * @magentoApiDataFixture Magento/GiftCardAccount/_files/giftcardaccount.php
     */
    public function testQueryGiftCardAccount()
    {
        $giftCardCode = 'giftcardaccount_fixture';
        $expectedExpirationDate = date('Y-m-d', strtotime('+1 week'));

        $query = $this->getGiftCardAccountQuery($giftCardCode);
        $result = $this->graphQlQuery($query);

        $this->assertArrayNotHasKey('errors', $result);
        $this->assertArrayHasKey('giftCardAccount', $result);
        $this->assertEquals($giftCardCode, $result['giftCardAccount']['code']);
        $this->assertEquals('USD', $result['giftCardAccount']['balance']['currency']);
        $this->assertEquals(9.99, $result['giftCardAccount']['balance']['value']);
        $this->assertEquals($expectedExpirationDate, $result['giftCardAccount']['expiration_date']);
    }

    /**
     * Test an error is returned when querying an inactive card
     *
     * @magentoApiDataFixture Magento/GiftCardAccount/_files/giftcardaccount_inactive.php
     */
    public function testQueryInactiveGiftCardAccount()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('GraphQL response contains errors: Invalid gift card');

        $giftCardCode = 'giftcardaccount_inactive';
        $query = $this->getGiftCardAccountQuery($giftCardCode);
        $this->graphQlQuery($query);
    }

    /**
     * Test an error is returned when code does not exist
     *
     */
    public function testQueryNonExistentGiftCardAccount()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('GraphQL response contains errors: No such entity');

        $giftCardCode = 'nonexistent_giftcard';
        $query = $this->getGiftCardAccountQuery($giftCardCode);
        $this->graphQlQuery($query);
    }

    /**
     * Test value is converted to other store currency
     *
     * @magentoApiDataFixture Magento/GiftCardAccount/_files/giftcardaccount.php
     * @magentoApiDataFixture Magento/Store/_files/second_store.php
     * @magentoApiDataFixture Magento/Store/_files/second_store_with_second_currency.php
     */
    public function testBalanceIsCorrectInOtherStoreCurrency()
    {
        $giftCardCode = 'giftcardaccount_fixture';

        $query = $this->getGiftCardAccountQuery($giftCardCode);
        $headers = ['Store' => 'fixture_second_store'];
        $result = $this->graphQlQuery($query, [], '', $headers);

        $this->assertArrayNotHasKey('errors', $result);
        $this->assertArrayHasKey('giftCardAccount', $result);
        $this->assertEquals($giftCardCode, $result['giftCardAccount']['code']);
        $this->assertEquals('EUR', $result['giftCardAccount']['balance']['currency']);
        $this->assertEquals(19.98, $result['giftCardAccount']['balance']['value']);
    }

    /**
     * Get query string for giftCardAccount query
     *
     * @param $code
     * @return string
     */
    private function getGiftCardAccountQuery($code)
    {
        return <<<QUERY
{
  giftCardAccount(input: {
    gift_card_code: "{$code}"
  }){
    code
    balance {
      currency
      value
    }
    expiration_date
  }
}
QUERY;
    }
}
