<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Plugin\Review;

use Magento\AdminGws\Model\Role as AdminGwsRole;
use Magento\Authorization\Model\Role as AuthorizationRole;
use Magento\Framework\App\ObjectManager;
use Magento\Review\Model\ResourceModel\Rating\Grid\Collection as GridCollection;
use Magento\TestFramework\Helper\Bootstrap as BootstrapHelper;
use PHPUnit\Framework\TestCase;

/**
 * Test rating size collection plugin
 */
class RatingCollectionSizeLimiterTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var GridCollection
     */
    private $gridCollection;

    /**
     * @var AuthorizationRole
     */
    private $adminRole;

    /**
     * @var AdminGwsRole
     */
    private $adminGwsRole;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = BootstrapHelper::getObjectManager();
        $this->adminRole = $this->objectManager->create(AuthorizationRole::class);
        $this->adminGwsRole = $this->objectManager->get(AdminGwsRole::class);
        $this->gridCollection = $this->objectManager->create(GridCollection::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        $this->adminRole->load('role_has_general_access', 'role_name');
        $this->adminGwsRole->setAdminRole($this->adminRole);
    }

    /**
     * Test getting real size of rating collection by restricted user
     *
     * @param string $roleName
     * @param int $collectionSize
     * @magentoDataFixture Magento/AdminGws/_files/one_rating_on_two_different_websites.php
     * @magentoAppArea adminhtml
     * @dataProvider getRolesAndSizeDataProvider
     */
    public function testGetSizeForRestrictedAdmin(string $roleName, int $collectionSize)
    {
        $this->adminRole->load($roleName, 'role_name');
        $this->adminGwsRole->setAdminRole($this->adminRole);
        $this->assertEquals($collectionSize, $this->gridCollection->getSize());
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
                'role_name' => 'role_has_test_website_access_only',
                'collection_size' => 1,
            ],
        ];
    }
}
