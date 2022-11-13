<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Model\Plugin;

use Magento\AdminGws\Model\Role as AdminGwsRole;
use Magento\Authorization\Model\Role as AuthorizationRole;
use Magento\Framework\App\ObjectManager;
use Magento\TestFramework\Helper\Bootstrap as BootstrapHelper;
use Magento\User\Model\ResourceModel\User\Collection as UserCollection;
use Magento\Authorization\Model\ResourceModel\Role\Grid\Collection as UserRoleCollection;
use PHPUnit\Framework\TestCase;

/**
 * Test User collection plugin
 */
class UserCollectionTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var UserCollection
     */
    private $userCollection;

    /**
     * @var AuthorizationRole
     */
    private $adminRole;

    /**
     * @var AdminGwsRole
     */
    private $adminGwsRole;

    /**
     * @var UserRoleCollection
     */
    private $userRoleCollection;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = BootstrapHelper::getObjectManager();
        $this->userCollection = $this->objectManager->create(UserCollection::class);
        $this->adminRole = $this->objectManager->create(AuthorizationRole::class);
        $this->adminGwsRole = $this->objectManager->get(AdminGwsRole::class);
        $this->userRoleCollection = $this->objectManager->create(UserRoleCollection::class);
    }

    /**
     * Test getting real size of user collection by restricted user
     *
     * @param string $roleName
     * @param int $collectionSize
     * @param int $userRoleCollectionSize
     * @magentoDataFixture Magento/AdminGws/_files/two_users_on_different_websites.php
     * @magentoAppArea adminhtml
     * @dataProvider getRolesAndSizeDataProvider
     */
    public function testGetSizeForRestrictedAdmin(string $roleName, int $collectionSize, int $userRoleCollectionSize)
    {
        $this->adminRole->load($roleName, 'role_name');
        $this->adminGwsRole->setAdminRole($this->adminRole);
        $this->assertEquals($collectionSize, $this->userCollection->getSize());
        $this->assertEquals($userRoleCollectionSize, $this->userRoleCollection->getSize());
        // restore admin role for proper rollback access
        $this->adminRole->load('role_has_general_access', 'role_name');
        $this->adminGwsRole->setAdminRole($this->adminRole);
    }

    /**
     * Data provider for testGetSizeForRestrictedAdmin
     *
     * @return array
     */
    public function getRolesAndSizeDataProvider(): array
    {
        return [
            [
                'role_name' => 'role_has_general_access',
                'collection_size' => 3,
                'userRoleCollectionSize' => 2
            ],
            [
                'role_name' => 'role_has_test_website_access_only',
                'collection_size' => 1,
                'userRoleCollectionSize' => 1
            ]
        ];
    }
}
