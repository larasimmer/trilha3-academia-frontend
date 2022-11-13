<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Authorization\Model\Role;
use Magento\TestFramework\Bootstrap as Bootstrap;
use Magento\TestFramework\Helper\Bootstrap as BootstrapHelper;
use Magento\User\Model\ResourceModel\User as UserResource;
use Magento\User\Model\User;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/AdminGws/_files/two_roles_for_different_websites.php');

/**
 * Create users with assigned role
 */
$objectManager = BootstrapHelper::getObjectManager();

$userRoles = [];
$roleNames = ['role_has_test_website_access_only', 'role_has_general_access'];
foreach ($roleNames as $name) {
    $userRoles[] = $objectManager->create(Role::class)->load($name, 'role_name');
}
/** @var UserResource $userResource */
$userResource = $objectManager->create(UserResource::class);
foreach ($userRoles as $role) {
    /** @var $user User */
    $user = $objectManager->create(User::class);
    $username = 'johnAdmin' . $role->getId();
    $email = 'JohnadminUser' . $role->getId() . '@example.com';
    $user->setFirstname("John")
        ->setIsActive(true)
        ->setLastname("Doe")
        ->setUsername($username)
        ->setPassword(Bootstrap::ADMIN_PASSWORD)
        ->setEmail($email)
        ->setRoleType($role->getRoleType())
        ->setResourceId('Magento_Backend::all')
        ->setPrivileges("")
        ->setAssertId(0)
        ->setRoleId($role->getId())
        ->setPermission('allow');
    $userResource->save($user);

    /** @var \Magento\Authorization\Model\Rules $rule */
    $rule = $objectManager->create(\Magento\Authorization\Model\Rules::class);
    $rule->setRoleId($role->getId())
        ->setResources(['Magento_Backend::all'])
        ->saveRel();
}
