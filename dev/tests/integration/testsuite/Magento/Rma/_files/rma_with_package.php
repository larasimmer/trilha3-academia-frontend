<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Rma\Api\TrackRepositoryInterface;
use Magento\Rma\Model\Shipping;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/Rma/_files/rma.php');

$objectManager = Bootstrap::getObjectManager();
/** @var TrackRepositoryInterface $trackRepository */
$trackRepository = $objectManager->get(TrackRepositoryInterface::class);
/** @var \Magento\Sales\Model\Order $order */
$order = $objectManager->get(OrderInterfaceFactory::class)->create()->loadByIncrementId('100000001');
$orderItems = $order->getItems();
$orderItem = reset($orderItems);

$orderProduct = $orderItem->getProduct();

/** @var Json $json */
$json = $objectManager->get(Json::class);
$packages = [
    [
        'params' => [
            'container' => '00',
            'weight' => '1',
            'customs_value' => '100',
            'length' => '1',
            'width' => '1',
            'height' => '1',
            'weight_units' => 'POUND',
            'dimension_units' => 'INCH',
            'content_type' => '',
            'content_type_other' => '',
            'delivery_confirmation' => '0',
        ],
        'items' => [
            [
                'qty' => '1',
                'customs_value' => '100',
                'price' => $orderProduct->getPrice(),
                'name' => $orderProduct->getName(),
                'weight' => $orderProduct->getWeight(),
                'product_id' => $orderProduct->getId(),
                'order_item_id' => $orderItem->getId(),
            ],
        ],
    ],
];
/** @var $trackingNumber Shipping */
$trackingNumber = $objectManager->create(Shipping::class)->load('TrackNumber', 'track_number');
$trackingNumber->setCarrierCode('ups')
    ->setIsAdmin(Shipping::IS_ADMIN_STATUS_ADMIN_LABEL)
    ->setPackages($json->serialize($packages));
$trackRepository->save($trackingNumber);
