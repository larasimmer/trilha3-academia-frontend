<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Magento\Review;

/**
 * @magentoAppArea adminhtml
 * @magentoDataFixture Magento/AdminGws/_files/role_websites_login.php
 */
class AssertButtonsRemovedTest extends \Magento\TestFramework\TestCase\AbstractBackendController
{
    protected function _getAdminCredentials()
    {
        return [
            'user' => 'admingws_user',
            'password' => 'admingws_password1'
        ];
    }

    public function testRatingAddButtonButtonShouldBeAbsent()
    {
        $this->dispatch('backend/review/rating/');
        $this->assertEquals(200, $this->getResponse()->getHttpResponseCode());
        $this->assertStringNotContainsString('Add New Rating', $this->getResponse()->getBody());
    }
}
