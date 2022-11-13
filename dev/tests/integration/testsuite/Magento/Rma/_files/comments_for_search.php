<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Rma\Model\Rma;
use Magento\Rma\Model\Rma\Status\History;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Rma\Api\RmaRepositoryInterface;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/Sales/_files/order.php');

$objectManager = Bootstrap::getObjectManager();
/** @var \Magento\Sales\Model\Order $order */
$order = $objectManager->get(OrderInterfaceFactory::class)->create()->loadByIncrementId('100000001');
/** @var $rma Rma */
$rma = Bootstrap::getObjectManager()->create(Rma::class);
$rma->setOrderId($order->getId());
$rma->setIncrementId(1);

/** @var RmaRepositoryInterface $rmaRepository */
$rmaRepository = Bootstrap::getObjectManager()->get(RmaRepositoryInterface::class);
$rmaRepository->save($rma);

$comments = [
    [
        'comment' => 'comment 1',
        'is_visible_on_front' => 1,
        'is_admin' => 1,
    ],
    [
        'comment' => 'comment 2',
        'is_visible_on_front' => 1,
        'is_admin' => 1,
    ],
    [
        'comment' => 'comment 3',
        'is_visible_on_front' => 1,
        'is_admin' => 1,
    ],
    [
        'comment' => 'comment 4',
        'is_visible_on_front' => 1,
        'is_admin' => 1,
    ],
    [
        'comment' => 'comment 5',
        'is_visible_on_front' => 0,
        'is_admin' => 1,
    ],
];

foreach ($comments as $data) {
    /** @var History $history */
    $history = Bootstrap::getObjectManager()->create(History::class);
    $history->setRma($rma);
    $history->setRmaEntityId($rma->getId());
    $history->saveComment($data['comment'], $data['is_visible_on_front'], $data['is_admin']);
}
