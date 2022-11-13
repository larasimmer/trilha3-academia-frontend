<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);


use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\SalesArchive\Model\Archive;
use Magento\SalesArchive\Model\Config;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/Sales/_files/order_state_hold.php');

$objectManager = Bootstrap::getObjectManager();
/** @var ScopeConfigInterface $scopeConfig */
$scopeConfig = $objectManager->get(ScopeConfigInterface::class);
$defaultActiveValue = $scopeConfig->getValue(Config::XML_PATH_ARCHIVE_ACTIVE);
$defaultValueStatuses = $scopeConfig->getValue(Config::XML_PATH_ARCHIVE_ORDER_STATUSES);
try {
    $scopeConfig->setValue(
        Config::XML_PATH_ARCHIVE_ACTIVE,
        '1',
        ScopeInterface::SCOPE_STORE
    );
    $scopeConfig->setValue(
        Config::XML_PATH_ARCHIVE_ORDER_STATUSES,
        'pending,processing,fraud,complete,closed,canceled,holded',
        ScopeInterface::SCOPE_STORE
    );
    /** @var Archive $archive */
    $archive = $objectManager->get(Archive::class);
    /** @var OrderInterface $order */
    $order = $objectManager->get(OrderInterfaceFactory::class)->create()->loadByIncrementId('100000001');
    $archive->archiveOrdersById($order->getId());
} finally {
    $scopeConfig->setValue(
        Config::XML_PATH_ARCHIVE_ORDER_STATUSES,
        $defaultValueStatuses,
        ScopeInterface::SCOPE_STORE
    );
    $scopeConfig->setValue(
        Config::XML_PATH_ARCHIVE_ACTIVE,
        $defaultActiveValue,
        ScopeInterface::SCOPE_STORE
    );
}
