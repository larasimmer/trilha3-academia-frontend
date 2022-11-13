<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Magento\Customer\Controller\Adminhtml;

use Magento\TestFramework\Bootstrap;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * Test for Customer edit action with restricted admin
 *
 * @magentoDataFixture Magento/Customer/_files/customer.php
 * @magentoDataFixture Magento/AdminGws/_files/role_on_second_website.php
 */
class EditTest extends AbstractBackendController
{
    /**
     * Test to deny access to view customer with other website
     *
     * @magentoConfigFixture customer/account_share/scope 0
     */
    public function testExecute()
    {
        $this->getRequest()->setParam('id', 1);
        $this->dispatch('backend/customer/index/edit');

        $this->assertRedirect($this->stringContains('/denied/'));
    }

    /**
     * @inheritdoc
     */
    protected function _getAdminCredentials()
    {
        return [
            'user' => 'customRoleUser',
            'password' => Bootstrap::ADMIN_PASSWORD,
        ];
    }
}
