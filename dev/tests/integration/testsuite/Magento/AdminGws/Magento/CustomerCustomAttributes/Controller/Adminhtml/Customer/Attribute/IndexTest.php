<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Magento\CustomerCustomAttributes\Controller\Adminhtml\Customer\Attribute;

use Magento\CustomerCustomAttributes\Controller\Adminhtml\Customer\Attribute;
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
    protected $resource = Attribute::ADMIN_RESOURCE;
    /**
     * @inheritDoc
     */
    protected $uri = 'backend/admin/customer_attribute/index';

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
     * Test that restricted admin user cannot add a new customer attribute
     */
    public function testAddButtonShouldBeAbsent()
    {
        $this->dispatch($this->uri);
        $this->assertEquals(200, $this->getResponse()->getHttpResponseCode());
        $this->assertStringNotContainsString('Add New Attribute', $this->getResponse()->getBody());
    }
}
