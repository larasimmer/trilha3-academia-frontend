<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\User\Model\ResourceModel\User as UserResource;
use Magento\User\Model\UserFactory;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

$objectManager = Bootstrap::getObjectManager();
/** @var UserResource $userResource */
$userResource = $objectManager->get(UserResource::class);
/** @var UserFactory $userFactory */
$userFactory = $objectManager->get(UserFactory::class);
/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

$user = $userFactory->create();
$user->loadByUsername('admingws_user');

if ($user->getId()) {
    $userResource->delete($user);
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);

Resolver::getInstance()->requireDataFixture('Magento/Store/_files/store_with_second_root_category_rollback.php');
Resolver::getInstance()->requireDataFixture('Magento/User/_files/user_with_custom_role_rollback.php');
