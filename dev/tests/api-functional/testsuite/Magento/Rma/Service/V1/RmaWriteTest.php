<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Service\V1;

use Magento\Rma\Model\Rma;
use Magento\Rma\Model\Item;
use Magento\TestFramework\TestCase\WebapiAbstract;

/**
 * Class RmaWriteTest
 */
class RmaWriteTest extends WebapiAbstract
{
    /**#@+
     * Constants defined for Web Api call
     */
    const SERVICE_VERSION = 'V1';
    const SERVICE_NAME = 'rmaRmaManagementV1';
    /**#@-*/

    /**
     * @var array
     */
    private $rmaComment = [
        'comment' => 'Item is approved',
        'customer_notified' => true,
        'visible_on_front' => true,
        'status' => 'Approved',
        'admin' => true
    ];

    /**
     * @var array
     */
    private $rmaTrack = [
        'track_number' => '324324324768',
        'carrier_title' => 'Custom',
        'carrier_code' => 'Fetcher'
    ];

    /**
     * @magentoApiDataFixture Magento/Sales/_files/shipment.php
     */
    public function testCreate()
    {
        $this->_markTestAsRestOnly('Fix inconsistencies in WSDL and Data interfaces');
        $rma = $this->getNewRmaData();

        $requestData = ['rmaDataObject' => $rma];
        $serviceInfo = [
            'rest' => [
                'resourcePath' => '/V1/returns',
                'httpMethod' => \Magento\Framework\Webapi\Rest\Request::HTTP_METHOD_POST,
            ],
            'soap' => [
                'service' => self::SERVICE_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_NAME . 'saveRma',
            ],
        ];
        $result = $this->_webApiCall($serviceInfo, $requestData);
        $this->assertNotNull($result[Rma::ENTITY_ID]);
        $this->assertNotEmpty($result[Rma::COMMENTS]);
        foreach ($result[Rma::COMMENTS] as $comment) {
            $this->assertNotNull($comment['rma_entity_id']);
            $this->assertNotNull($comment['created_at']);
            $this->assertNotNull($comment['entity_id']);
            $this->assertNotNull($comment['comment']);
            $this->assertEquals($comment['comment'], $this->rmaComment['comment']);
            $this->assertEquals($comment['customer_notified'], $this->rmaComment['customer_notified']);
            $this->assertEquals($comment['visible_on_front'], $this->rmaComment['visible_on_front']);
            $this->assertEquals($comment['status'], $this->rmaComment['status']);
            $this->assertEquals($comment['admin'], $this->rmaComment['admin']);
        }
        $this->assertNotEmpty($result[Rma::TRACKS]);
        foreach ($result[Rma::TRACKS] as $track) {
            $this->assertNotNull($track['entity_id']);
            $this->assertNotNull($track['rma_entity_id']);
            $this->assertNotNull($track['track_number']);
            $this->assertEquals($track['track_number'], $this->rmaTrack['track_number']);
            $this->assertEquals($track['carrier_title'], $this->rmaTrack['carrier_title']);
            $this->assertEquals($track['carrier_code'], $this->rmaTrack['carrier_code']);
        }
        $this->assertNotNull($result[Rma::ITEMS][0][Item::ENTITY_ID]);
        $this->assertNotEmpty($result);
    }

    /**
     * @magentoApiDataFixture Magento/Sales/_files/shipment.php
     */
    public function testUpdate()
    {
        $this->_markTestAsRestOnly('Fix inconsistencies in WSDL and Data interfaces');
        $rmaData = $this->getNewRmaData();

        $requestData = ['rmaDataObject' => $rmaData];
        $serviceInfo = [
            'rest' => [
                'resourcePath' => '/V1/returns',
                'httpMethod' => \Magento\Framework\Webapi\Rest\Request::HTTP_METHOD_POST,
            ],
            'soap' => [
                'service' => self::SERVICE_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_NAME . 'saveRma',
            ],
        ];
        $this->_webApiCall($serviceInfo, $requestData);

        $rma = $this->getRmaFixture();

        $requestData = ['rmaDataObject' => $this->getRequestForUpdateRma($rma)];
        $serviceInfo = [
            'rest' => [
                'resourcePath' => '/V1/returns/' . $rma->getId(),
                'httpMethod' => \Magento\Framework\Webapi\Rest\Request::HTTP_METHOD_PUT,
            ],
            'soap' => [
                'service' => self::SERVICE_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_NAME . 'saveRma',
            ],
        ];
        $result = $this->_webApiCall($serviceInfo, array_merge(['id' => $rma->getId()], $requestData));
        $this->assertNotNull($result[Rma::ENTITY_ID]);
        $this->assertNotEmpty($result);
    }

    /**
     * @return array
     */
    private function getNewRmaData()
    {
        $objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $collection = $objectManager->create(\Magento\Sales\Model\ResourceModel\Order\Collection::class);
        $collection->setOrder('entity_id')
            ->setPageSize(1)
            ->load();
        /** @var \Magento\Sales\Model\Order $order */
        $order = $collection->fetchItem();
        $items = $order->getItemsCollection();

        $request = $this->getRmaInitData();
        $request[Rma::ORDER_ID] = $order->getId();
        $request[Rma::STORE_ID] = $order->getStoreId();
        $request[Rma::ITEMS] = [];
        $request[Rma::DATE_REQUESTED] = $request[Rma::DATE_REQUESTED] ?? 'now';
        $request[Rma::COMMENTS] = [0 => $this->rmaComment];
        $request[Rma::TRACKS] = [0 => $this->rmaTrack];
        $request[Rma::STATUS] = 'pending';

        /** @var \Magento\Sales\Model\Order\Item $item */
        foreach ($items as $item) {
            $request[Rma::ITEMS][] = [
                Item::ENTITY_ID => null,
                Item::RMA_ENTITY_ID => null,
                Item::ORDER_ITEM_ID => $item->getId(),
                Item::QTY_REQUESTED => 1,
                Item::CONDITION => 7,
                Item::REASON => 9,
                Item::RESOLUTION => 4,
                Item::STATUS => 'pending',
                Item::QTY_AUTHORIZED => null,
                Item::QTY_APPROVED => null,
                Item::QTY_RETURNED => null,
            ];
            $item->setProductType('simple');
            $item->setQtyShipped($item->getQtyOrdered());
            $item->save();
        }

        $order->save();

        return $request;
    }

    /**
     * Return last created Rma fixture
     *
     * @return \Magento\Rma\Model\Rma
     */
    private function getRmaFixture()
    {
        $objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $collection = $objectManager->create(\Magento\Rma\Model\ResourceModel\Rma\Collection::class);
        $collection->setOrder('entity_id')
            ->setPageSize(1)
            ->load();
        return $collection->fetchItem();
    }

    /**
     * @param \Magento\Rma\Model\Rma $rma
     * @return array
     */
    private function getRequestForUpdateRma(\Magento\Rma\Model\Rma $rma)
    {
        $request = $this->getRmaInitData();
        $request[Rma::ENTITY_ID] = $rma->getEntityId();
        $request[Rma::STATUS] = 'authorized';
        $request[Rma::ORDER_ID] = $rma->getOrderId();
        $request[Rma::STORE_ID] = $rma->getStoreId();
        $request[Rma::ITEMS] = [];
        $request[Rma::DATE_REQUESTED] = $request[Rma::DATE_REQUESTED] ?? 'now';

        /** @var \Magento\Rma\Model\Item $item */
        foreach ($rma->getItemsForDisplay() as $item) {
            $request[Rma::ITEMS][] = [
                Item::ENTITY_ID => $item->getId(),
                Item::RMA_ENTITY_ID => $rma->getEntityId(),
                Item::ORDER_ITEM_ID => $item->getOrderItemId(),
                Item::QTY_AUTHORIZED => 1,
                Item::CONDITION => 7,
                Item::REASON => 9,
                Item::RESOLUTION => 4,
                Item::STATUS => 'authorized',
                Item::QTY_REQUESTED => null,
                Item::QTY_APPROVED => null,
                Item::QTY_RETURNED => null,
            ];
        }
        return $request;
    }

    /**
     * @return array
     */
    private function getRmaInitData()
    {
        return [
            Rma::ENTITY_ID => null,
            Rma::ORDER_ID => null,
            Rma::ORDER_INCREMENT_ID => null,
            Rma::INCREMENT_ID => null,
            Rma::STORE_ID => null,
            Rma::CUSTOMER_ID => null,
            Rma::DATE_REQUESTED => null,
            Rma::CUSTOMER_CUSTOM_EMAIL => null,
            Rma::ITEMS => null,
            Rma::STATUS => null,
            Rma::COMMENTS => null,
            Rma::TRACKS => null,
        ];
    }
}
