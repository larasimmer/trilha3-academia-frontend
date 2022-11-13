<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Framework\Registry;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\TestFramework\Helper\Bootstrap;

/** @var Bootstrap */
$objectManager = Bootstrap::getObjectManager();

/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

$orderCollection = $objectManager->create(Collection::class);
/** @var $order Order */
foreach ($orderCollection as $order) {
    $order->delete();
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
