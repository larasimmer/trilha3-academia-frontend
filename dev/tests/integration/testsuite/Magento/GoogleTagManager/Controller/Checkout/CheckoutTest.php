<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GoogleTagManager\Controller\Checkout;

/**
 * @magentoAppArea frontend
 * @magentoDataFixture Magento/Sales/_files/quote.php
 * @magentoDataFixture Magento/Customer/_files/customer.php
 */
class CheckoutTest extends \Magento\Multishipping\Controller\CheckoutTest
{
    /**
     * Check that google tag manager doesn't affect multishipping process
     *
     * @magentoConfigFixture current_store multishipping/options/checkout_multiple 1
     * @magentoConfigFixture current_store google/analytics/active 1
     * @magentoConfigFixture current_store google/analytics/type tag_manager
     * @magentoConfigFixture current_store google/analytics/container_id container_id
     */
    public function testOverviewAction()
    {
        return parent::testOverviewAction();
    }
}
