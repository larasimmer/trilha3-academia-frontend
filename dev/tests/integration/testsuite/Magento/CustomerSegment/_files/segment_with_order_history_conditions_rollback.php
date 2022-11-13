<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\TestFramework\Helper\Bootstrap;
use Magento\CustomerSegment\Model\Segment;
use Magento\Framework\Registry;

$objectManager = Bootstrap::getObjectManager();
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

$segment = $objectManager->get(Segment::class);
$segment->load('Segment with order history condition', 'name');
if ($segment->getId()) {
    $segment->delete();
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
