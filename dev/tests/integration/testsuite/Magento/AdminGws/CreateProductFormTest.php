<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws;

use Magento\Authorization\Model\Role;
use Magento\AdminGws\Model\Role as AdminGwsRole;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\Websites;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * @magentoAppArea adminhtml
 */
class CreateProductFormTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * SetUp
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * Tests case when admin has User Role with access only to one website
     * and tries to create a product. Only allowed website should be present
     * in 'Product in Websites' section.
     *
     * @magentoDataFixture Magento/Store/_files/second_website_with_two_stores.php
     * @magentoDataFixture Magento/AdminGws/_files/role_websites_login.php
     */
    public function testWebsitesInProductForm()
    {
        /** @var Role $adminRole */
        $adminRole = $this->objectManager->get(Role::class);
        $adminRole->load('admingws_role', 'role_name');

        /** @var \Magento\AdminGws\Model\Role $adminGwsRole */
        $adminGwsRole = $this->objectManager->get(AdminGwsRole::class);
        $adminGwsRole->setAdminRole($adminRole);

        $product = $this->objectManager->create(Product::class);
        /** @var \Magento\Framework\Registry $registry */
        $registry = $this->objectManager->get(\Magento\Framework\Registry::class);
        $registry->register('current_product', $product);

        $allowedWebsiteId = $this->objectManager->get(StoreManagerInterface::class)
            ->getWebsite()
            ->getId();

        /** @var Websites $websites */
        $websites = $this->objectManager->get(Websites::class);
        $meta = $websites->modifyMeta([]);

        $this->assertArrayHasKey(
            $allowedWebsiteId,
            $meta['websites']['children'],
            'Website allowed for current admin role should be present in websites section'
        );

        $this->assertCount(
            1,
            $meta['websites']['children'],
            'Only one website is allowed for current admin role and should be present in websites section'
        );
    }
}
