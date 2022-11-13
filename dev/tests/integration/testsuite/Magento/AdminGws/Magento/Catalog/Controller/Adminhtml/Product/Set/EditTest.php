<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Magento\Catalog\Controller\Adminhtml\Product\Set;

use Magento\Catalog\Controller\Adminhtml\Product\Set;
use Magento\Catalog\Model\Product;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * @magentoAppArea adminhtml
 * @magentoDataFixture Magento/AdminGws/_files/role_websites_login.php
 */
class EditTest extends AbstractBackendController
{
    /**
     * @inheritDoc
     */
    protected $resource = Set::ADMIN_RESOURCE;
    /**
     * @inheritDoc
     */
    protected $uri = 'backend/catalog/product_set/edit';

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->uri .= '/id/' . $this->_objectManager->create(Product::class)->getDefaultAttributeSetid();
    }

    /**
     * @inheritDoc
     */
    protected function _getAdminCredentials()
    {
        return [
            'user' => 'admingws_user',
            'password' => 'admingws_password1'
        ];
    }

    /**
     * Test that restricted admin user cannot modify attribute set
     */
    public function testEditButtonsShouldBeAbsent()
    {
        $this->dispatch($this->uri);
        $this->assertEquals(200, $this->getResponse()->getHttpResponseCode());
        $this->assertStringNotContainsString('Save', $this->getResponse()->getBody());
        $this->assertStringNotContainsString('Reset', $this->getResponse()->getBody());
        $this->assertStringNotContainsString('Add New', $this->getResponse()->getBody());
        $this->assertStringNotContainsString('Delete Selected Group', $this->getResponse()->getBody());
    }
}
