<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\CustomerSegment\Model\Segment;
use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

$segment = $objectManager->get(Segment::class);
$segment->load('Customer Segment with zero orders', 'name');
if ($segment->getId()) {
    $segment->delete();
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
