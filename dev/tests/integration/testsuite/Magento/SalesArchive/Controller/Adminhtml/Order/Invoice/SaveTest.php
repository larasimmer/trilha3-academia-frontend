<?php
/***
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesArchive\Controller\Adminhtml\Order\Invoice;

use Magento\Sales\Controller\Adminhtml\Order\Invoice\AbstractInvoiceControllerTest;
use Magento\SalesArchive\Model\ArchivalList;
use Magento\SalesArchive\Model\ResourceModel\Archive as ArchiveResource;

/**
 * Testing adding invoice to archive.
 *
 * @magentoAppArea adminhtml
 */
class SaveTest extends AbstractInvoiceControllerTest
{
    /** @var ArchiveResource */
    private $archiveResource;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->archiveResource = $this->_objectManager->get(ArchiveResource::class);
    }

    /**
     * @magentoConfigFixture current_store sales/magento_salesarchive/active 1
     * @magentoConfigFixture current_store sales/magento_salesarchive/order_statuses pending,processing,complete
     *
     * @magentoDataFixture Magento/SalesArchive/_files/archived_pending_order.php
     *
     * @return void
     */
    public function testPartialInvoiceForArchivedOrder(): void
    {
        $order = $this->getOrder('100000001');
        $post = $this->hydratePost([$order->getItemsCollection()->getFirstItem()->getId() => 1]);
        $this->prepareRequest($post, ['order_id' => $order->getEntityId()]);
        $this->dispatch('backend/sales/order_invoice/save');
        $this->assertRedirect(
            $this->stringContains(sprintf('sales/order/view/order_id/%u', (int)$order->getEntityId()))
        );
        $this->assertSessionMessages($this->containsEqual((string)__('The invoice has been created.')));
        $invoice = $this->getInvoiceByOrder($order);
        $this->assertNotNull($invoice->getId());
        $archivedInvoiceId = $this->archiveResource->getIdsInArchive(ArchivalList::INVOICE, [$invoice->getId()]);
        $this->assertEquals([$invoice->getId()], $archivedInvoiceId);
    }
}
