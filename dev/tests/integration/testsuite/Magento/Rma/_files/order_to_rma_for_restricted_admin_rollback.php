<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Authorization\Model\RoleFactory;
use Magento\Authorization\Model\Rules;
use Magento\Authorization\Model\RulesFactory;
use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;
use Magento\User\Model\User;

Resolver::getInstance()->requireDataFixture('Magento/Sales/_files/default_rollback.php');
Resolver::getInstance()->requireDataFixture('Magento/Customer/_files/customer_rollback.php');
$productsRollbackPath = 'Magento/Catalog/_files/category_with_different_price_products_on_two_websites_rollback.php';
Resolver::getInstance()->requireDataFixture($productsRollbackPath);

$objectManager = Bootstrap::getObjectManager();
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);
/**
 * Deleting the user and the role.
 */
/** @var $user User */
$user = $objectManager->create(User::class);
$role = $objectManager->get(RoleFactory::class)->create();
$role->load('role_has_test_website_access_only', 'role_name');
if ($role->getId()) {
    /** @var Rules $rules */
    $rules = $objectManager->get(RulesFactory::class)->create();
    $rules->load($role->getId(), 'role_id');
    $rules->delete();
    $username = 'johnAdmin' . $role->getId();
    $user->loadByUsername($username)->delete();
    $role->delete();
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
