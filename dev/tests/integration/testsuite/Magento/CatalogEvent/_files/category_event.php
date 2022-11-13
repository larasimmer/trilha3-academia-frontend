<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/** @var $event \Magento\CatalogEvent\Model\Event */
$event = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
    \Magento\CatalogEvent\Model\Event::class
);
$event->setStoreId(0);
$event->setCategoryId('3');
$event->setStoreDateStart(date('Y-m-d H:i:s'))->setStoreDateEnd(date('Y-m-d H:i:s', time() + 3600));
$event->save();
