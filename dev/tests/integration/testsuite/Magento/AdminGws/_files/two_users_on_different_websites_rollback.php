<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Authorization\Model\Role;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\User\Model\User;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

/**
 * Delete users with assigned role
 */
$objectManager = Bootstrap::getObjectManager();
/** @var $user User */
$user = $objectManager->create(User::class);
$role = $objectManager->create(Role::class);
foreach (['role_has_test_website_access_only', 'role_has_general_access'] as $roleName) {
    $role->load($roleName, 'role_name');
    if ($role->getId()) {
        $username = 'johnAdmin' . $role->getId();
        $user->loadByUsername($username)->delete();
    }
}
Resolver::getInstance()->requireDataFixture('Magento/AdminGws/_files/two_roles_for_different_websites_rollback.php');
