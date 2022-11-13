<?php
/***
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesArchive\Controller\Adminhtml\Archive;

use Magento\Framework\App\Request\Http;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * Class consist of base logic for sales mass actions tests
 */
abstract class AbstractMassActionTest extends AbstractBackendController
{
    /** @var OrderInterfaceFactory */
    private $orderFactory;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->orderFactory = $this->_objectManager->get(OrderInterfaceFactory::class);
    }

    /**
     * Prepare request
     *
     * @param array $orderIds
     * @return void
     */
    protected function prepareRequest(array $orderIds): void
    {
        $selected = [];
        foreach ($orderIds as $orderId) {
            $selected[] = $this->getOrder($orderId)->getId();
        }

        $postParams = [
            'selected' => $selected,
            'namespace' => 'sales_archive_order_grid',
        ];
        $this->getRequest()->setMethod(Http::METHOD_POST);
        $this->getRequest()->setPostValue($postParams);
    }

    /**
     * Load order by increment id
     *
     * @param string $orderIncrementId
     * @return OrderInterface
     */
    private function getOrder(string $orderIncrementId): OrderInterface
    {
        $order = $this->orderFactory->create();

        return $order->loadByIncrementId($orderIncrementId);
    }
}
