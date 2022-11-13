<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DeferredTotalCalculating\Plugin;

use Magento\Quote\Model\Quote;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResource;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class TotalsCollectorPluginTest extends TestCase
{
    /**
     * @var QuoteResource
     */
    private $quoteResource;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->quoteResource = $objectManager->create(QuoteResource::class);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/DeferredTotalCalculating/_files/deferred_total_calculating.php
     */
    public function testAroundCollectTotals()
    {
        /** @var Quote $quote */
        $quote = Bootstrap::getObjectManager()->create(Quote::class);
        $this->quoteResource->load($quote, 'test01', 'reserved_order_id');
        $quote->collectTotals();
        $this->assertEquals(10, $quote->getBaseSubtotal());
        $this->assertEquals(1, $quote->getItemsQty());
    }
}
