<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Rma\Api\RmaRepositoryInterface;
use Magento\Rma\Model\Rma;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/Sales/_files/order.php');

$objectManager = Bootstrap::getObjectManager();
$order = $objectManager->get(OrderInterfaceFactory::class)->create()->loadByIncrementId('100000001');

$comments = [
    [
        'status' => 'status 1',
        'increment_id' => 101,
        'date_requested' => '2016-08-02 03:00:00',
        'customer_custom_email' => 'custom1@custom.net',
    ],
    [
        'status' => 'status 2',
        'increment_id' => 505,
        'date_requested' => '2016-08-02 04:00:00',
        'customer_custom_email' => 'custom1@custom.net',
    ],
    [
        'status' => 'status 3',
        'increment_id' => 202,
        'date_requested' => '2016-08-02 06:00:00',
        'customer_custom_email' => 'custom1@custom.net',
    ],
    [
        'status' => 'status 4',
        'increment_id' => 404,
        'date_requested' => '2016-08-02 05:00:00',
        'customer_custom_email' => 'custom1@custom.net',
    ],
    [
        'status' => 'status 5',
        'increment_id' => 303,
        'date_requested' => '2016-08-02 00:00:00',
        'customer_custom_email' => 'custom2@custom.net',
    ],
];

/** @var RmaRepositoryInterface $rmaRepository */
$rmaRepository = $objectManager->get(RmaRepositoryInterface::class);

foreach ($comments as $data) {
    /** @var $rma Rma */
    $rma = $objectManager->create(Rma::class);
    $rma->setOrderId($order->getId());
    $rma->setStatus($data['status']);
    $rma->setIncrementId($data['increment_id']);
    $rma->setDateRequested($data['date_requested']);
    $rma->setCustomerCustomEmail($data['customer_custom_email']);
    $rmaRepository->save($rma);
}
