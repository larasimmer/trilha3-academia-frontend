<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws;

use Magento\Reports\Model\ResourceModel\Quote\Item\Collection;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * @magentoDataFixture Magento/AdminGws/_files/two_quote_items_on_different_websites.php
 * @magentoAppIsolation enabled
 * @magentoDbIsolation disabled
 * @magentoAppArea adminhtml
 */
class QuoteItemCollectionUpdaterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Collection
     */
    protected $collection;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->collection = Bootstrap::getObjectManager()->create(Collection::class);
    }

    /**
     * Tests that Cart Items are prepared including Admin access restriction
     *
     * @param string $adminName
     * @param int $expected
     * @dataProvider prepareActiveCartItemsDataProvider
     */
    public function testPrepareActiveCartItems(string $adminName, int $expected)
    {
        $objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

        /** @var \Magento\Authorization\Model\Role $adminRole */
        $adminRole = $objectManager->get(\Magento\Authorization\Model\Role::class);
        $adminRole->load($adminName, 'role_name');

        /** @var \Magento\AdminGws\Model\Role $adminGwsRole */
        $adminGwsRole = $objectManager->get(\Magento\AdminGws\Model\Role::class);
        $adminGwsRole->setAdminRole($adminRole);

        $this->collection->prepareActiveCartItems();
        $items = $this->collection->getItems();

        $this->assertCount($expected, $items);

        // restore admin role for proper rollback access
        $adminRole->load('role_has_general_access', 'role_name');
        $adminGwsRole->setAdminRole($adminRole);
    }

    /**
     * @return array
     */
    public function prepareActiveCartItemsDataProvider() : array
    {
        return [
            'restricted role' => ['role_has_test_website_access_only', 1],
            'unrestricted role' => ['role_has_general_access', 2],
        ];
    }
}
