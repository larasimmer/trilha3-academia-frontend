<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * @magentoAppArea adminhtml
 * @magentoDataFixture Magento/AdminGws/_files/role_websites_login.php
 */
class ProductLinkInGridTest extends \Magento\TestFramework\TestCase\AbstractBackendController
{
    /**
     * Get credentials to login restricted admin user
     *
     * @return array
     */
    protected function _getAdminCredentials()
    {
        return [
            'user' => 'admingws_user',
            'password' => 'admingws_password1'
        ];
    }
    /**
     * Tests case when admin has User Role with access only to one website.
     * Allowed store id should be present in product link on product grid page.
     *
     * @magentoDataFixture Magento/AdminGws/_files/role_websites_login.php
     * @magentoDataFixture Magento/Catalog/_files/product_with_two_websites.php
     */
    public function testStoreExistsInProductLink()
    {
        $this->getRequest()->setParam('namespace', 'product_listing');

        $this->dispatch('/backend/mui/index/render/');

        $content = $this->getResponse()->getBody();
        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $this->_objectManager->get(ProductRepositoryInterface::class);
        $product = $productRepository->get('unique-simple-azaza');
        $store = $this->_objectManager->get(StoreManagerInterface::class)
            ->getStore();

        $this->assertStringContainsString(
            'backend\/catalog\/product\/edit\/id\/' . $product->getId() . '\/store\/' . $store->getId(),
            $content,
            'Store id must be present in product link for user who doesn\'t have exclusive access to product'
        );
    }
}
