<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Model;

use Magento\Authorization\Model\Acl\Role\User;
use Magento\Authorization\Model\ResourceModel\Role as RoleResource;
use Magento\Authorization\Model\ResourceModel\Role\Collection;
use Magento\Authorization\Model\Role;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * @magentoAppArea adminhtml
 */
class AuthorizationRoleSaveTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var RoleResource
     */
    private $roleResource;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->roleResource = $objectManager->create(RoleResource::class);

        $roleName = 'users';
        $websites = [];
        $limit = 260;
        for ($i = 0; $i < $limit; $i++) {
            $websites[] = 1;
        }

        /** @var Role $role */
        $role = Bootstrap::getObjectManager()->create(Role::class);
        $role->setParentId(1)
            ->setTreeLevel(1)
            ->setSortOrder(0)
            ->setRoleType(User::ROLE_TYPE)
            ->setUserId(0)
            ->setUserType('2')
            ->setRoleName($roleName)
            ->setGwsWebsites($websites);

        $this->roleResource->save($role);
    }

    /**
     * Tests that authorization role save don't loose websites data
     */
    public function testAuthorizationRoleSave()
    {
        $roleName = 'users';
        $expected = 260;
        /** @var Collection $userCollection */
        $roleCollection = Bootstrap::getObjectManager()->create(Collection::class);
        $roles = $roleCollection->load();

        foreach ($roles as $role) {
            if ($role->getRoleName() == $roleName) {
                $this->assertEquals($expected, count(explode(',', $role->getGwsWebsites())));

                $this->roleResource->delete($role);
            }
        }
    }
}
