<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
$registry = $objectManager->get(\Magento\Framework\Registry::class);

$banner = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
    \Magento\Banner\Model\Banner::class
);

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

try {
    $banner->load('Test Dynamic Block', 'name');
    $banner->delete();
} catch (\Exception $ex) {
    //Nothing to remove
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
