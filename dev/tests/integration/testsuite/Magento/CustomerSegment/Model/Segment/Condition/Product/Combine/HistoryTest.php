<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerSegment\Model\Segment\Condition\Product\Combine;

use Magento\CustomerSegment\Model\Segment\Condition\Product\Combine\History as History;
use Magento\TestFramework\Helper\Bootstrap;

class HistoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var History
     */
    private $subject;
    /**
     * @inheritdoc
     */

    protected function setUp(): void
    {
        $this->subject = Bootstrap::getObjectManager()
            ->create(History::class);
    }

    /**
     * Test that no products of the orders are fetched for guest customers.
     *
     * @magentoDataFixture Magento/Sales/_files/customer_order_with_simple_product.php
     */
    public function testNoOrdersFetchedForGuest()
    {
        $this->subject->setValue(History::ORDERED);
        $this->subject->isSatisfiedBy(null, 1, []);

        $productIds = $this->subject->getProductIds();
        $this->assertEquals(null, $productIds);
    }
}
