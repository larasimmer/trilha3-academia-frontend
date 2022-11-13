<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Magento\Catalog\Controller\Adminhtml\Product;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Type;
use Magento\CatalogInventory\Model\Stock\StockItemRepository;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Message\MessageInterface;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * Test CRUD operations with restricted admin users.
 *
 * @magentoAppArea adminhtml
 * @magentoDataFixture Magento/AdminGws/_files/role_websites_login.php
 */
class CRUDTest extends AbstractBackendController
{
    /** @var ProductRepositoryInterface */
    private $productRepository;

    /** @var StockItemRepository */
    private $stockItemRepository;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->productRepository = $this->_objectManager->get(ProductRepositoryInterface::class);
        $this->stockItemRepository = $this->_objectManager->get(StockItemRepository::class);
    }

    /**
     * Try to create product by store level permission admin user.
     *
     * @dataProvider getProductDataProvider
     * @magentoDataFixture Magento/AdminGws/_files/role_stores_login.php
     * @param array $postData
     * @return void
     */
    public function testCreateProductOnStoreLevel(array $postData): void
    {
        $this->markTestSkipped('https://github.com/magento/partners-magento2ee/issues/149');

        $this->getRequest()->setPostValue($postData);
        $this->getRequest()->setMethod(Http::METHOD_POST);
        $this->dispatch('backend/catalog/product/save/');

        $this->assertEquals('noroute', $this->getRequest()->getControllerName());
        $this->assertContains('Page not found.', $this->getResponse()->getBody());
    }

    /**
     * Try to update product with edited catalog inventory by store level permission admin user.
     *
     * @magentoDataFixture Magento/Catalog/_files/product_virtual.php
     * @magentoDataFixture Magento/AdminGws/_files/role_stores_login.php
     * @return void
     */
    public function testUpdateProductWithCatalogInventoryOnStoreLevel(): void
    {
        $postData = [
            'product' => [
                'stock_data' => [
                    [
                        'min_sale_qty' => 10,
                        'use_config_min_sale_qty' => 0,
                    ]
                ],
            ],
        ];
        $product = $this->productRepository->get('virtual-product', false, null, true);
        $this->getRequest()->setPostValue($postData);
        $this->getRequest()->setMethod(Http::METHOD_POST);
        $this->dispatch('backend/catalog/product/save/id/' . (int)$product->getEntityId());
        $this->assertSessionMessages(
            $this->containsEqual('The stock item was unable to be saved. Please try again.'),
            MessageInterface::TYPE_ERROR
        );
    }

    /**
     * Try to delete product by store level permission admin user.
     *
     * @magentoDataFixture Magento/Catalog/_files/product_virtual.php
     * @magentoDataFixture Magento/AdminGws/_files/role_stores_login.php
     * @return void
     */
    public function testDeleteProductOnStoreLevel(): void
    {
        $product = $this->productRepository->get('virtual-product', false, null, true);
        $postData = [
            'selected' => [(int)$product->getEntityId()],
            'namespace' => 'product_listing',
        ];
        $this->getRequest()->setPostValue($postData);
        $this->getRequest()->setMethod(Http::METHOD_POST);
        $this->dispatch('backend/catalog/product/massDelete');
        $this->assertSessionMessages(
            $this->containsEqual(
                "A total of 1 record(s) haven&#039;t been deleted. Please see server logs for more details."
            ),
            MessageInterface::TYPE_ERROR
        );
    }

    /**
     * Get post data for the request.
     *
     * @return array
     */
    public function getProductDataProvider(): array
    {
        return [
            'Simple product' => [
                [
                    'product' => [
                        'attribute_set_id' => '4',
                        'status' => '1',
                        'name' => 'Simple Product',
                        'sku' => 'simple-TSG',
                        'url_key' => 'simple-product-TSG',
                        'type_id' => Type::TYPE_SIMPLE,
                    ],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function _getAdminCredentials()
    {
        return [
            'user' => 'admingws_user',
            'password' => 'admingws_password1'
        ];
    }
}
