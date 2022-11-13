<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Framework\Registry;
use Magento\Reward\Model\ResourceModel\Reward\Rate\Collection;
use Magento\TestFramework\Helper\Bootstrap;

/** @var Registry $registry */
$registry = Bootstrap::getObjectManager()->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var Collection $collection */
$collection = Bootstrap::getObjectManager()->create(Collection::class);
foreach ($collection as $item) {
    $item->delete();
}
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
