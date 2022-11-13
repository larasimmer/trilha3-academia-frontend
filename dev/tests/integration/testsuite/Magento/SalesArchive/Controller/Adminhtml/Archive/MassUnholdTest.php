<?php
/***
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesArchive\Controller\Adminhtml\Archive;

/**
 * Checks mass unhold archived orders
 *
 * @see \Magento\Sales\Controller\Adminhtml\Order\MassUnhold
 *
 * @magentoAppArea adminhtml
 */
class MassUnholdTest extends AbstractMassActionTest
{
    /**
     * @magentoDataFixture Magento/SalesArchive/_files/archived_holded_order.php
     *
     * @return void
     */
    public function testUnHoldArchivedOrder(): void
    {
        $this->prepareRequest(['100000001']);
        $this->dispatch('backend/sales/order/massUnHold/');
        $this->assertSessionMessages(
            $this->containsEqual((string)__('%1 order(s) have been released from on hold status.', 1))
        );
    }

    /**
     * @magentoDataFixture Magento/SalesArchive/_files/archived_pending_order.php
     *
     * @return void
     */
    public function testUnHoldArchivedPendingOrder(): void
    {
        $this->prepareRequest(['100000001']);
        $this->dispatch('backend/sales/order/massUnHold/');
        $this->assertSessionMessages(
            $this->containsEqual((string)__('No order(s) were released from on hold status.'))
        );
    }
}
