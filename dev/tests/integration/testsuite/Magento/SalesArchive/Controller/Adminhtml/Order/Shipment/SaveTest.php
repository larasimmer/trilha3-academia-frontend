<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesArchive\Controller\Adminhtml\Order\Shipment;

use Magento\Framework\App\Request\Http;
use Magento\Framework\Message\MessageInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\SalesArchive\Model\ArchivalList;
use Magento\SalesArchive\Model\ResourceModel\Archive as ArchiveResource;
use Magento\Shipping\Controller\Adminhtml\Order\Shipment\AbstractShipmentControllerTest;

/**
 * Testing adding invoice to archive.
 *
 * @magentoAppArea adminhtml
 */
class SaveTest extends AbstractShipmentControllerTest
{
    /** @var ArchiveResource */
    private $archiveResource;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->archiveResource = $this->_objectManager->create(ArchiveResource::class);
    }

    /**
     * @magentoConfigFixture current_store sales/magento_salesarchive/active 1
     * @magentoConfigFixture current_store sales/magento_salesarchive/order_statuses pending,processing,complete
     *
     * @magentoDataFixture Magento/SalesArchive/_files/archived_pending_order.php
     *
     * @return void
     */
    public function testPartiallyShipPendingArchivedOrder(): void
    {
        $order = $this->getOrder('100000001');
        $itemsToShip = 1;
        $this->prepareRequest(
            ['order_id' => $order->getEntityId()],
            $this->hydratePost([$order->getItemsCollection()->getFirstItem()->getId() => $itemsToShip])
        );
        $this->dispatch('backend/admin/order_shipment/save');
        $this->assertSuccess($order, $itemsToShip);
    }

    /**
     * @magentoConfigFixture current_store sales/magento_salesarchive/active 1
     * @magentoConfigFixture current_store sales/magento_salesarchive/order_statuses pending,processing,complete
     *
     * @magentoDataFixture Magento/SalesArchive/_files/archived_invoiced_order.php
     *
     * @return void
     */
    public function testShipProcessingArchivedOrder(): void
    {
        $order = $this->getOrder('100000001');
        $itemsToShip = 2;
        $this->prepareRequest(
            ['order_id' => $order->getEntityId()],
            $this->hydratePost([$order->getItemsCollection()->getFirstItem()->getId() => $itemsToShip])
        );
        $this->dispatch('backend/admin/order_shipment/save');
        $this->assertSuccess($order, $itemsToShip);
    }

    /**
     * Assert success shipment and archiving
     *
     * @param OrderInterface $order
     * @param int $itemsToShip
     * @return void
     */
    private function assertSuccess(OrderInterface $order, int $itemsToShip): void
    {
        $this->assertSessionMessages(
            $this->containsEqual((string)__('The shipment has been created.')),
            MessageInterface::TYPE_SUCCESS
        );
        $this->assertRedirect($this->stringContains('sales/order/view/order_id/' . $order->getEntityId()));
        $shipment = $this->getShipment($order);
        $this->assertNotNull($shipment->getId());
        $archivedShipmentId = $this->archiveResource->getIdsInArchive(ArchivalList::SHIPMENT, [$shipment->getId()]);
        $this->assertEquals($archivedShipmentId, [$shipment->getId()]);
        $shipmentItems = $shipment->getItems();
        $item = reset($shipmentItems);
        $this->assertEquals($itemsToShip, (int)$item->getQty());
    }

    /**
     * Prepare request
     *
     * @param array $params
     * @param array $postParams
     * @return void
     */
    private function prepareRequest(array $params, array $postParams): void
    {
        $this->getRequest()->setMethod(Http::METHOD_POST);
        $this->getRequest()->setParams($params);
        $this->getRequest()->setPostValue($postParams);
    }

    /**
     * Normalize post parameters
     *
     * @param array $items
     * @return array
     */
    private function hydratePost(array $items): array
    {
        return [
            'shipment' => [
                'items' => $items,
            ],
        ];
    }
}
