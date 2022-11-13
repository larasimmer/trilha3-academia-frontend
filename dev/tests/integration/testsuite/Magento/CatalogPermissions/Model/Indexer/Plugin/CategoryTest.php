<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Model\Indexer\Plugin;

use Magento\TestFramework\Helper\Bootstrap;

/**
 * @magentoDbIsolation enabled
 */
class CategoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CatalogPermissions\Model\ResourceModel\Permission\Index
     */
    protected $permissionIndex;

    /**
     * @var \Magento\Framework\Indexer\IndexerInterface
     */
    protected $indexer;

    /**
     * @var \Magento\Catalog\Model\Category
     */
    protected $category;

    protected function setUp(): void
    {
        Bootstrap::getObjectManager()->addSharedInstance(
            Bootstrap::getObjectManager()->create(
                \Magento\Framework\Authorization::class,
                ['aclPolicy' => new \Magento\Framework\Authorization\Policy\DefaultPolicy()]
            ),
            // phpstan:ignore "Class Magento\Framework\AuthorizationInterface\Proxy not found."
            \Magento\Framework\AuthorizationInterface\Proxy::class
        );

        $this->permissionIndex = Bootstrap::getObjectManager()->create(
            \Magento\CatalogPermissions\Model\ResourceModel\Permission\Index::class
        );

        $this->category = Bootstrap::getObjectManager()->create(\Magento\Catalog\Model\Category::class);
    }

    /**
     * Override permissions for NotLoggedIn Customers (group=0)
     * when a permission for All Customer Groups is also set
     *
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/enabled true
     */
    public function testSavePermissionsForAllAndNotLoggedInGroups()
    {
        $websiteId = Bootstrap::getObjectManager()->get(
            \Magento\Store\Model\StoreManagerInterface::class
        )->getWebsite()->getId();
        $permissionsDataDenyNotLoggedIn = [
            'website_id' => $websiteId,
            'customer_group_id' => 0,
            'grant_catalog_category_view' => \Magento\CatalogPermissions\Model\Permission::PERMISSION_DENY,
            'grant_catalog_product_price' => \Magento\CatalogPermissions\Model\Permission::PERMISSION_DENY,
            'grant_checkout_items' => \Magento\CatalogPermissions\Model\Permission::PERMISSION_DENY,
        ];
        $this->category->setData('permissions', [1 => $permissionsDataDenyNotLoggedIn]);
        $this->category->setName('Test Category');
        $this->category->save();
        $categoryId = $this->category->getId();
        $this->assertTrue(
            in_array(
                array_merge(
                    $permissionsDataDenyNotLoggedIn,
                    [
                        'category_id' => $categoryId,
                        'customer_group_id' => 0
                    ]
                ),
                $this->permissionIndex->getIndexForCategory($categoryId)
            )
        );

        $permissionsDataAllowAll = [
            'website_id' => $websiteId,
            'customer_group_id' => null,
            'grant_catalog_category_view' => \Magento\CatalogPermissions\Model\Permission::PERMISSION_ALLOW,
            'grant_catalog_product_price' => \Magento\CatalogPermissions\Model\Permission::PERMISSION_ALLOW,
            'grant_checkout_items' => \Magento\CatalogPermissions\Model\Permission::PERMISSION_ALLOW,
        ];
        $this->category->setData('permissions', [1 => $permissionsDataAllowAll]);
        $this->category->save();
        $this->assertTrue(
            in_array(
                array_merge(
                    $permissionsDataDenyNotLoggedIn,
                    [
                        'category_id' => $categoryId,
                        'customer_group_id' => 0
                    ]
                ),
                $this->permissionIndex->getIndexForCategory($categoryId)
            )
        );
    }

    protected function tearDown(): void
    {
        Bootstrap::getObjectManager()->removeSharedInstance(
        // phpstan:ignore "Class Magento\Framework\AuthorizationInterface\Proxy not found."
            \Magento\Framework\AuthorizationInterface\Proxy::class
        );
    }
}
