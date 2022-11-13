<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Authorization\Model\Role;
use Magento\Authorization\Model\RoleFactory;
use Magento\Authorization\Model\Rules;
use Magento\Authorization\Model\RulesFactory;
use Magento\Store\Model\Website;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/Store/_files/second_website_with_store_group_and_store.php');
Resolver::getInstance()->requireDataFixture('Magento/User/_files/user_with_custom_role.php');

/** @var Website $website */
$website = Bootstrap::getObjectManager()->create(Website::class);
$website->load('test', 'code');

/** @var Role $role */
$role = Bootstrap::getObjectManager()->get(RoleFactory::class)->create();
$role->load('test_custom_role', 'role_name');
$role->setGwsIsAll(0)
    ->setGwsWebsites($website->getId())
    ->save();

/** @var Rules $rules */
$rules = Bootstrap::getObjectManager()->get(RulesFactory::class)->create();
$rules->setRoleId($role->getId());
$rules->setResources([Bootstrap::getObjectManager()->get(\Magento\Framework\Acl\RootResource::class)->getId()]);
$rules->saveRel();
