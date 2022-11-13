<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Authorization\Model\ResourceModel\Role as RoleResource;
use Magento\Authorization\Model\RoleFactory;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\Store\Model\Website;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;
use Magento\User\Model\ResourceModel\User as UserResource;
use Magento\User\Model\UserFactory;

Resolver::getInstance()->requireDataFixture('Magento/Store/_files/store_with_second_root_category.php');
Resolver::getInstance()->requireDataFixture('Magento/User/_files/user_with_custom_role.php');

$objectManager = Bootstrap::getObjectManager();
/** @var WebsiteRepositoryInterface $websiteRepository */
$websiteRepository = $objectManager->get(WebsiteRepositoryInterface::class);
$website = $websiteRepository->get('test');
/** @var RoleFactory $roleFactory */
$roleFactory = $objectManager->get(RoleFactory::class);
/** @var RoleResource $roleResource */
$roleResource = $objectManager->get(RoleResource::class);
$role = $roleFactory->create();
$roleResource->load($role, 'test_custom_role', 'role_name');
$role->setGwsIsAll(0)
    ->setGwsWebsites($website->getId());
$roleResource->save($role);
/** @var UserFactory $userFactory */
$userFactory = $objectManager->get(UserFactory::class);
$user = $userFactory->create();
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
/** @var UserResource $userResource */
$userResource = $objectManager->get(UserResource::class);
$user->setRoleId($role->getId());
$userResource->save($user);
