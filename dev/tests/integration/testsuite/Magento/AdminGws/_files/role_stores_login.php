<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Authorization\Model\Role;
use Magento\Authorization\Model\Rules;
use Magento\Backend\App\Area\FrontNameResolver;
use Magento\Framework\App\Area;
use Magento\Framework\App\AreaList;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\User\Model\User;

$objectManager = Bootstrap::getObjectManager();
$objectManager->get(AreaList::class)->getArea(FrontNameResolver::AREA_CODE)->load(Area::PART_CONFIG);

/** @var $role Role */
$role = $objectManager->create(Role::class);
$role->setName('admingws_role')->setGwsIsAll(0)->setRoleType('G')->setPid('1');

$role->setGwsStoreGroups(
    $objectManager->get(StoreManagerInterface::class)->getWebsite()->getDefaultGroupId()
);

$role->save();

/** @var $rule Rules */
$rule = $objectManager->create(Rules::class);
$rule->setRoleId($role->getId())->setResources(['Magento_Backend::all'])->saveRel();

$user = $objectManager->create(User::class);
$user->setData(
    [
        'firstname' => 'firstname',
        'lastname' => 'lastname',
        'email' => 'admingws@example.com',
        'username' => 'admingws_user',
        'password' => 'admingws_password1',
        'is_active' => 1,
    ]
);

$user->setRoleId($role->getId())->save();
