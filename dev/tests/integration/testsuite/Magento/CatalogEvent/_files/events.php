<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\CatalogEvent\Model\Event;
use Magento\CatalogEvent\Model\EventFactory;
use Magento\CatalogEvent\Model\ResourceModel\Event as EventResource;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
/** @var EventFactory $eventFactory */
$eventFactory = $objectManager->get(EventFactory::class);
/** @var EventResource $eventResource */
$eventResource = $objectManager->get(EventResource::class);

$eventClosed = $eventFactory->create();
$eventClosed->setCategoryId(null)
    ->setDateStart(date('Y-m-d H:i:s', strtotime('-1 year')))
    ->setDateEnd(date('Y-m-d H:i:s', strtotime('-1 month')))
    ->setDisplayState(Event::DISPLAY_CATEGORY_PAGE)
    ->setSortOrder(30)
    ->setImage('default_website.jpg');
$eventResource->save($eventClosed);

$eventClosed->setStoreId(1)
    ->setImage('default_store_view.jpg');
$eventResource->save($eventClosed);

$eventOpen = $eventFactory->create();
$eventOpen->setCategoryId(1)
    ->setDateStart(date('Y-m-d H:i:s', strtotime('-1 month')))
    ->setDateEnd(date('Y-m-d H:i:s', strtotime('+1 month')))
    ->setDisplayState(Event::DISPLAY_PRODUCT_PAGE)
    ->setSortOrder(20)
    ->setImage('default_website.jpg');
$eventResource->save($eventOpen);

$eventUpcoming = $eventFactory->create();
$eventUpcoming->setCategoryId(2)
    ->setDateStart(date('Y-m-d H:i:s', strtotime('+1 month')))
    ->setDateEnd(date('Y-m-d H:i:s', strtotime('+1 year')))
    ->setDisplayState(Event::DISPLAY_CATEGORY_PAGE | Event::DISPLAY_PRODUCT_PAGE)
    ->setSortOrder(10)
    ->setStoreId(1)
    ->setImage('default_store_view.jpg');
$eventResource->save($eventUpcoming);
