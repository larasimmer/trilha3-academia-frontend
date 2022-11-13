<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerBalance\Plugin\Model\Quote;

use Magento\TestFramework\Helper\Bootstrap;
use Magento\Quote\Model\Quote;

/**
 * Test for Magento\CustomerBalance\Plugin\Model\Quote\ResetCustomerBalanceUsage
 */
class ResetCustomerBalanceUsageTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/quote_with_customer.php
     * @dataProvider testDataProvider
     * @param bool $useCustomerBalance
     * @param bool $expected
     *
     * @return void
     */
    public function testAfterRemoveItem(bool $useCustomerBalance, bool $expected): void
    {
        $quote = Bootstrap::getObjectManager()->create(Quote::class);
        $quote->load('test01', 'reserved_order_id');

        $quote->setUseCustomerBalance($useCustomerBalance);
        $quote->removeItem($quote->getAllItems()[0]->getItemId());

        $this->assertEquals($expected, $quote->getUseCustomerBalance());
    }

    /**
     * @return array
     */
    public function testDataProvider(): array
    {
        return [
            [true, false],
            [false, false],
        ];
    }
}
