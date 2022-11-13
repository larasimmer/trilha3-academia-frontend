<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

$website = $objectManager->create(\Magento\Store\Model\Website::class);
$website->setName('Test Website')
    ->setCode('test_website')
    ->save();

$group = $objectManager->create(\Magento\Store\Model\Group::class);
$group->setName('Test Store (Group)')
    ->setCode('test_store')
    ->setWebsiteId($website->getId())
    ->save();

$store = $objectManager->create(\Magento\Store\Model\Store::class);
$store->setName('Test Store View (Store)')
    ->setCode('test_store_view')
    ->setIsActive(1)
    ->setWebsiteId($website->getId())
    ->setGroupId($group->getId())
    ->save();

$role = $objectManager->create(\Magento\Authorization\Model\Role::class);
$role->setName('role_has_test_website_access_only')
    ->setGwsIsAll(0)
    ->setRoleType('G')
    ->setGwsWebsites($website->getId())
    ->save();

$role = $objectManager->create(\Magento\Authorization\Model\Role::class);
$role->setName('role_has_general_access')
    ->setGwsIsAll(1)
    ->setRoleType('U')
    ->save();
