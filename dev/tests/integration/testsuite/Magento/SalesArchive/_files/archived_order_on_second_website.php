<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\SalesArchive\Model\Archive;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture(
    'Magento/Sales/_files/order_with_invoice_shipment_creditmemo_on_second_website.php'
);

$objectManager = Bootstrap::getObjectManager();
/** @var Archive $archive */
$archive = $objectManager->get(Archive::class);
/** @var \Magento\Sales\Model\Order $order */
$order = $objectManager->get(OrderInterfaceFactory::class)->create()->loadByIncrementId('200000001');
$archive->archiveOrdersById($order->getId());
