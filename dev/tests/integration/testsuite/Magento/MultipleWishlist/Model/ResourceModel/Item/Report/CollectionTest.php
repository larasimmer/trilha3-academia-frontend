<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MultipleWishlist\Model\ResourceModel\Item\Report;

use Magento\AdminGws\Model\Role as AdminGwsRole;
use Magento\Authorization\Model\Role as AuthorizationRole;
use Magento\Framework\DB\Select;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppIsolation enabled
 * @magentoAppArea adminhtml
 */
class CollectionTest extends TestCase
{
    /**
     * @var Collection
     */
    private $collection;

    protected function setUp(): void
    {
        $this->collection = Bootstrap::getObjectManager()->create(Collection::class);
    }

    public function testAddCustomerInfo()
    {
        $joinParts = $this->collection->getSelect()->getPart(Select::FROM);
        $this->assertArrayHasKey('customer', $joinParts);
    }

    /**
     * @magentoDataFixture Magento/MultipleWishlist/_files/wishlists.php
     */
    public function testGetSize()
    {
        $this->assertEquals(2, $this->collection->getSize());
    }

    /**
     * @magentoDataFixture Magento/MultipleWishlist/_files/wishlists.php
     */
    public function testLoad()
    {
        $this->collection->load();
        $this->assertCount(2, $this->collection->getItems());
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture Magento/AdminGws/_files/two_roles_for_different_websites.php
     * @magentoDataFixture Magento/MultipleWishlist/_files/wishlists.php
     */
    public function testGetSizeForRestrictedAdmin()
    {
        $adminRole = Bootstrap::getObjectManager()->get(AuthorizationRole::class);
        $adminRole->load('role_has_test_website_access_only', 'role_name');
        $adminGwsRole = Bootstrap::getObjectManager()->get(AdminGwsRole::class);
        $adminGwsRole->setAdminRole($adminRole);

        $this->assertEquals(0, $this->collection->getSize());

        // restore admin role for proper rollback access
        $adminRole->load('role_has_general_access', 'role_name');
        $adminGwsRole->setAdminRole($adminRole);
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture Magento/AdminGws/_files/two_roles_for_different_websites.php
     * @magentoDataFixture Magento/MultipleWishlist/_files/wishlists.php
     */
    public function testLoadForRestrictedAdmin()
    {
        $adminRole = Bootstrap::getObjectManager()->get(AuthorizationRole::class);
        $adminRole->load('role_has_test_website_access_only', 'role_name');
        $adminGwsRole = Bootstrap::getObjectManager()->get(AdminGwsRole::class);
        $adminGwsRole->setAdminRole($adminRole);

        $this->collection->load();
        $this->assertCount(0, $this->collection->getItems());

        // restore admin role for proper rollback access
        $adminRole->load('role_has_general_access', 'role_name');
        $adminGwsRole->setAdminRole($adminRole);
    }
}
