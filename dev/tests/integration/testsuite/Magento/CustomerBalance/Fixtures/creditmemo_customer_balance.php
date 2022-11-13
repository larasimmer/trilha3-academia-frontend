<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Sales\Api\CreditmemoItemRepositoryInterface;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoItemInterface;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/CustomerBalance/Fixtures/order_customer_balance.php');

/** @var ObjectManager $objectManager */
$objectManager = Bootstrap::getObjectManager();
/** @var \Magento\Sales\Model\Order $order */
$order = $objectManager->get(OrderInterfaceFactory::class)->create()->loadByIncrementId('100000002');
/** @var CreditmemoFactory $creditMemoFactory */
$creditMemoFactory = $objectManager->get(CreditmemoFactory::class);
$creditMemo = $creditMemoFactory->createByOrder($order, $order->getData());
$creditMemo->setOrder($order);
$creditMemo->setState(Creditmemo::STATE_OPEN);
$creditMemo->setIncrementId('100000002');

/** @var CreditmemoRepositoryInterface $creditMemoRepository */
$creditMemoRepository = $objectManager->get(CreditmemoRepositoryInterface::class);
$creditMemoRepository->save($creditMemo);

/** @var OrderItemInterface $orderItem */
$orderItem = current($order->getAllItems());
$orderItem->setName('Test item')
    ->setQtyRefunded(10)
    ->setQtyInvoiced(10);
$grandTotal = 50.00;
/** @var CreditmemoItemInterface $item */
$item = $objectManager->create(CreditmemoItemInterface::class);
$item->setParentId($creditMemo->getId())
    ->setName('Creditmemo item')
    ->setOrderItemId($orderItem->getItemId())
    ->setQty(10)
    ->setPrice($grandTotal);

/** @var CreditmemoItemRepositoryInterface $itemRepository */
$itemRepository = $objectManager->get(CreditmemoItemRepositoryInterface::class);
$itemRepository->save($item);

$order->setTotalPaid($grandTotal);
$order->setBaseTotalPaid($grandTotal);
$order->setBaseCustomerBalanceInvoiced($grandTotal);
/** @var OrderRepositoryInterface $orderRepository */
$orderRepository = $objectManager->get(OrderRepositoryInterface::class);
$orderRepository->save($order);
