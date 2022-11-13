<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Model;

use Magento\AdminGws\Model\Role as GwsRole;
use Magento\Authorization\Model\ResourceModel\Role\Collection as RoleCollection;
use Magento\Authorization\Model\RoleFactory;
use Magento\Framework\App\ObjectManager;
use Magento\TestFramework\Bootstrap;
use Magento\TestFramework\Helper\Bootstrap as Helper;
use Magento\User\Model\ResourceModel\User\Collection as UserCollection;

/**
 * Test for checking limited admin permissions collection.
 *
 * @magentoAppArea adminhtml
 */
class CollectionsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Collections
     */
    private $model;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var RoleFactory
     */
    private $roleFactory;

    /**
     * @var GwsRole
     */
    private $gwsRole;

    /**
     * @var RoleCollection
     */
    private $roleCollection;

    /**
     * @var UserCollection
     */
    private $userCollection;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Helper::getObjectManager();
        $this->model = $this->objectManager->get(Collections::class);
        $this->roleFactory = $this->objectManager->get(RoleFactory::class);
        $this->roleCollection = $this->objectManager->create(RoleCollection::class);
        $this->userCollection = $this->objectManager->create(UserCollection::class);
        $this->gwsRole = $this->objectManager->get(GwsRole::class);
    }

    /**
     * Test limit admin permission roles.
     *
     * @param array $data
     * @param int $resultsCount
     * @return void
     * @throws \Exception
     * @magentoDbIsolation enabled
     * @dataProvider limitAdminPermissionDataProvider
     */
    public function testLimitAdminPermissionRoles(array $data, int $resultsCount)
    {
        $role = $this->roleFactory->create()->load(Bootstrap::ADMIN_ROLE_NAME, 'role_name');
        $this->gwsRole->setAdminRole($role);
        $role->addData($data)->save();
        $this->roleCollection->setRolesFilter();
        $this->assertCount($resultsCount, $this->roleCollection->getItems());
    }

    /**
     * Test limit admin permission users.
     *
     * @param array $data
     * @param int $resultsCount
     * @return void
     * @throws \Exception
     * @magentoDbIsolation enabled
     * @dataProvider limitAdminPermissionDataProvider
     */
    public function testLimitAdminPermissionUsers(array $data, int $resultsCount)
    {
        $role = $this->roleFactory->create()->load(Bootstrap::ADMIN_ROLE_NAME, 'role_name');
        $this->gwsRole->setAdminRole($role);
        $role->addData($data)->save();
        $this->assertCount($resultsCount, $this->userCollection->getItems());
    }

    /**
     * @return array
     */
    public function limitAdminPermissionDataProvider(): array
    {
        return [
            [['gws_is_all' => 1, 'gws_websites' => null, 'gws_store_groups' => null], 1],
            [['gws_is_all' => 0, 'gws_websites' => [1], 'gws_store_groups' => [1]], 0],
        ];
    }
}
