<?php
/***
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesArchive\Controller\Adminhtml\Archive;

use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Message\MessageInterface;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\SalesArchive\Model\Archive;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * Class to test Archive mass add controller
 * @magentoAppArea adminhtml
 */
class MassAddTest extends AbstractBackendController
{
    /** @var OrderInterfaceFactory */
    private $orderFactory;

    /** @var Archive */
    private $archive;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->resource = 'Magento_SalesArchive::add';
        $this->uri = 'backend/sales/archive/massadd';
        $this->httpMethod = HttpRequest::METHOD_POST;
        $this->orderFactory = $this->_objectManager->get(OrderInterfaceFactory::class);
        $this->archive = $this->_objectManager->create(Archive::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        $this->archive->removeOrdersFromArchive();

        parent::tearDown();
    }

    /**
     * Preparing and dispatching request to update orders status
     *
     * @param int $orderId
     * @return void
     */
    private function dispatchPreparedRequest(int $orderId): void
    {
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue([
            'selected' => [$orderId],
            'namespace' => 'sales_order_grid'
        ]);
        $this->dispatch($this->uri);
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Sales/_files/order_new.php
     * @magentoConfigFixture default_store sales/magento_salesarchive/order_statuses pending,closed
     * @return void
     */
    public function testMoveOrderInStatusPendingToArchive(): void
    {
        $order = $this->orderFactory->create()->loadByIncrementId('100000001');
        $this->dispatchPreparedRequest((int)$order->getId());
        $this->assertSessionMessages(
            $this->containsEqual((string)__('We archived %1 order(s).', 1)),
            MessageInterface::TYPE_SUCCESS
        );
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Sales/_files/order_with_invoice_shipment_creditmemo.php
     * @magentoConfigFixture default_store sales/magento_salesarchive/order_statuses pending,closed,complete
     * @return void
     */
    public function testMoveOrderInStatusCompleteToArchive(): void
    {
        $order = $this->orderFactory->create()->loadByIncrementId('100000111');
        $this->dispatchPreparedRequest((int)$order->getId());
        $this->assertSessionMessages(
            $this->containsEqual((string)__('We archived %1 order(s).', 1)),
            MessageInterface::TYPE_SUCCESS
        );
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store sales/magento_salesarchive/order_statuses pending,closed
     * @return void
     */
    public function testMoveOrderInStatusProcessingToArchive(): void
    {
        $order = $this->orderFactory->create()->loadByIncrementId('100000001');
        $this->dispatchPreparedRequest((int)$order->getId());
        $this->assertSessionMessages(
            $this->containsEqual((string)__('We can\'t archive the selected order(s).')),
            MessageInterface::TYPE_WARNING
        );
    }
}
