<?php
/***
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesArchive\Controller\Adminhtml\Archive;

/**
 * Checks mass hold archived orders
 *
 * @see \Magento\Sales\Controller\Adminhtml\Order\MassHold
 *
 * @magentoAppArea adminhtml
 */
class MassHoldTest extends AbstractMassActionTest
{
    /**
     * @magentoDataFixture Magento/SalesArchive/_files/archived_pending_order.php
     *
     * @return void
     */
    public function testHoldArchivedOrder(): void
    {
        $this->prepareRequest(['100000001']);
        $this->dispatch('backend/sales/order/massHold/');
        $this->assertSessionMessages($this->containsEqual((string)__('You have put %1 order(s) on hold.', 1)));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order_complete.php
     *
     * @return void
     */
    public function testHoldCompleteOrder(): void
    {
        $this->prepareRequest(['100000333']);
        $this->dispatch('backend/sales/order/massHold/');
        $this->assertSessionMessages($this->containsEqual((string)__('No order(s) were put on hold.')));
    }
}
