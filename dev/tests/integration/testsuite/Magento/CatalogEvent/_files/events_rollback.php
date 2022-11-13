<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\CatalogEvent\Model\ResourceModel\Event as EventResource;
use Magento\CatalogEvent\Model\ResourceModel\Event\Collection;
use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
/** @var EventResource $eventResource */
$eventResource = $objectManager->get(EventResource::class);
/** @var Collection $eventCollection */
$eventCollection = $objectManager->get(Collection::class);
/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

$events = $eventCollection->addFieldToFilter('category_id', ['in' => [null, 1, 2]]);
foreach ($events as $event) {
    try {
        $eventResource->delete($event);
    } catch (\Exception $exception) {
        // Nothing to delete
    }
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
