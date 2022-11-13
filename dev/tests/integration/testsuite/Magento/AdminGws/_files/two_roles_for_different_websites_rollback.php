<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

$registry = $objectManager->get(\Magento\Framework\Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

foreach (['role_has_test_website_access_only', 'role_has_general_access'] as $roleName) {
    $role = $objectManager->create(Magento\Authorization\Model\Role::class);
    $role->load($roleName, 'role_name');
    if ($role->getId()) {
        $role->delete();
    }
}

$store = $objectManager->create(Magento\Store\Model\Store::class);
$store->load('test_store_view', 'code');
if ($store->getId()) {
    $store->delete();
}
$group = $objectManager->create(Magento\Store\Model\Group::class);
$group->load('test_store', 'code');
if ($group->getId()) {
    $group->delete();
}
$website = $objectManager->create(Magento\Store\Model\Website::class);
$website->load('test_website', 'code');
if ($website->getId()) {
    $website->delete();
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
