<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\User\Model\UserFactory;
use Magento\User\Model\ResourceModel\User as ResourceUser;
use Magento\Authorization\Model\ResourceModel\Role as ResourceRole;

$objectManager = Bootstrap::getObjectManager();

/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var ResourceUser $resourceUser */
$resourceUser = $objectManager->get(ResourceUser::class);
/** @var ResourceRole $resourceRole */
$resourceRole = $objectManager->get(ResourceRole::class);
/** @var UserFactory $userFactory */
$userFactory = $objectManager->get(UserFactory::class);
$userModel = $userFactory->create();

$user = $userModel->loadByUsername('admingws_user');
$userRole = $user->getRole();

if ($user->getId()) {
    $resourceUser->delete($user);
}
if ($userRole->getId()) {
    $resourceRole->delete($userRole);
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
