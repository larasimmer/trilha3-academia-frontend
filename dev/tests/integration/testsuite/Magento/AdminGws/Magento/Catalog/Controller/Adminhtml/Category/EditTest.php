<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Magento\Catalog\Controller\Adminhtml\Category;

use Magento\TestFramework\Helper\Xpath;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * Checks that admin will see allowed root category instead of default
 *
 * @magentoAppArea adminhtml
 * @magentoDataFixture Magento/AdminGws/_files/role_on_second_website_login.php
 * @magentoDbIsolation enabled
 */
class EditTest extends AbstractBackendController
{
    private const CATEGORY_TITLE_XPATH_PATTERN = "//h1[contains(@class, 'page-title') and contains(text(), '%s')]";

    /**
     * @return void
     */
    public function testCategoriesView(): void
    {
        $this->dispatch('backend/catalog/category/index');
        $body = $this->getResponse()->getBody();
        $this->assertNotEmpty($body);
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(
                sprintf(self::CATEGORY_TITLE_XPATH_PATTERN, 'Second Root Category'),
                $body
            )
        );
    }

    /**
     * @inheritdoc
     */
    protected function _getAdminCredentials()
    {
        return [
            'user' => 'admingws_user',
            'password' => 'admingws_password1',
        ];
    }
}
