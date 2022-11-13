<?php
/***
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesArchive\Controller\Adminhtml\Archive;

use Magento\Framework\Message\MessageInterface;

/**
 * Checks mass cancel archived orders
 *
 * @see \Magento\Sales\Controller\Adminhtml\Order\MassCancel
 *
 * @magentoAppArea adminhtml
 */
class MassCancelTest extends AbstractMassActionTest
{
    /**
     * @magentoDataFixture Magento/SalesArchive/_files/archived_pending_order.php
     *
     * @return void
     */
    public function testCancelArchivedPendingOrder(): void
    {
        $this->prepareRequest(['100000001']);
        $this->dispatch('backend/sales/order/massCancel/');
        $this->assertSessionMessages($this->containsEqual((string)__('We canceled %1 order(s).', 1)));
    }

    /**
     * @magentoDataFixture Magento/SalesArchive/_files/archived_order_closed.php
     *
     * @return void
     */
    public function testCancelArchivedClosedOrder(): void
    {
        $this->prepareRequest(['100001111']);
        $this->dispatch('backend/sales/order/massCancel/');
        $this->assertSessionMessages($this->containsEqual((string)__('You cannot cancel the order(s).', 1)));
    }

    /**
     * @magentoDataFixture Magento/SalesArchive/_files/archived_pending_order.php
     * @magentoDataFixture Magento/SalesArchive/_files/archived_order_closed.php
     *
     * @return void
     */
    public function testCancelArchivedPendingAndClosedOrder(): void
    {
        $this->prepareRequest(['100000001', '100001111']);
        $this->dispatch('backend/sales/order/massCancel/');
        $this->assertSessionMessages(
            $this->containsEqual((string)__('%1 order(s) cannot be canceled.', 1)),
            MessageInterface::TYPE_ERROR
        );
        $this->assertSessionMessages(
            $this->containsEqual((string)__('We canceled %1 order(s).', 1)),
            MessageInterface::TYPE_SUCCESS
        );
    }
}
