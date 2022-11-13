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

Resolver::getInstance()->requireDataFixture('Magento/Sales/_files/order_closed.php');
$objectManager = Bootstrap::getObjectManager();
/** @var OrderInterfaceFactory $orderFactory */
$orderFactory = $objectManager->get(OrderInterfaceFactory::class);
$order = $orderFactory->create()->loadByIncrementId('100001111');
/** @var Archive $archive */
$archive = $objectManager->create(Archive::class);
$archive->archiveOrdersById($order->getId());
