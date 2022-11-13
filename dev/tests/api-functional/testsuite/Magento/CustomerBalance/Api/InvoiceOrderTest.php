<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerBalance\Api;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\ResourceModel\Order\Invoice\Collection;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\WebapiAbstract;

/**
 * Test invoice order with customer balance
 */
class InvoiceOrderTest extends WebapiAbstract
{
    const RESOURCE_PATH = '/V1/invoices';
    const SERVICE_CREATE_INVOICE_NAME = 'salesInvoiceOrderV1';
    const SERVICE_INVOICE_REPOSITORY_NAME = 'salesInvoiceRepositoryV1';
    const SERVICE_VERSION = 'V1';

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * @magentoApiDataFixture Magento/CustomerBalance/_files/order_with_customer_balance.php
     */
    public function testExecute()
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->objectManager->create(\Magento\Sales\Model\Order::class)
            ->loadByIncrementId('100000001');

        $serviceInfo = [
            'rest' => [
                'resourcePath' => '/V1/order/' . $order->getId() . '/invoice',
                'httpMethod' => \Magento\Framework\Webapi\Rest\Request::HTTP_METHOD_POST,
            ],
            'soap' => [
                'service' => self::SERVICE_CREATE_INVOICE_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_CREATE_INVOICE_NAME . 'execute',
            ],
        ];

        $requestData = [
            'orderId' => $order->getId(),
            'items' => [],
            'comment' => [
                'comment' => 'Test Comment',
                'is_visible_on_front' => 1,
            ],
        ];

        /** @var \Magento\Sales\Api\Data\OrderItemInterface $item */
        foreach ($order->getAllItems() as $item) {
            $requestData['items'][] = [
                'order_item_id' => $item->getItemId(),
                'qty' => $item->getQtyOrdered(),
            ];
        }
        $invoiceId = (int)$this->_webApiCall($serviceInfo, $requestData);
        $this->assertNotEmpty($invoiceId);

        $invoiceData = $this->getInvoiceData($invoiceId);
        $this->assertArrayHasKey('base_customer_balance_amount', $invoiceData['extension_attributes']);
        $this->assertArrayHasKey('customer_balance_amount', $invoiceData['extension_attributes']);
        $this->assertEquals(8, $invoiceData['extension_attributes']['base_customer_balance_amount']);
        $this->assertEquals(8, $invoiceData['extension_attributes']['customer_balance_amount']);
    }

    /**
     * Load invoice data
     *
     * @param int $id
     * @return array
     */
    private function getInvoiceData(int $id): array
    {
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . '/' . $id,
                'httpMethod' => Request::HTTP_METHOD_GET,
            ],
            'soap' => [
                'service' => self::SERVICE_INVOICE_REPOSITORY_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_INVOICE_REPOSITORY_NAME . 'get',
            ],
        ];

        return (array)$this->_webApiCall($serviceInfo, ['id' => $id]);
    }
}
