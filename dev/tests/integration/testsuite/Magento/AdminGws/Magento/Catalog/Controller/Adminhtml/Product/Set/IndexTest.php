<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Magento\Catalog\Controller\Adminhtml\Product\Set;

use Magento\Catalog\Controller\Adminhtml\Product\Set;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * @magentoAppArea adminhtml
 * @magentoDataFixture Magento/AdminGws/_files/role_websites_login.php
 */
class IndexTest extends AbstractBackendController
{
    /**
     * @inheritDoc
     */
    protected $resource = Set::ADMIN_RESOURCE;
    /**
     * @inheritDoc
     */
    protected $uri = 'backend/catalog/product_set/index';

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
     * Test that restricted admin user cannot add a new attribute set
     */
    public function testAddButtonShouldBeAbsent()
    {
        $this->dispatch($this->uri);
        $this->assertEquals(200, $this->getResponse()->getHttpResponseCode());
        $this->assertStringNotContainsString('Add Attribute Set', $this->getResponse()->getBody());
    }
}
