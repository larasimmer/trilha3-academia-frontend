<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogPermissions\Controller;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Cache\StateInterface as CacheStateInterface;
use Magento\Framework\App\PageCache\Kernel as PageCacheKernel;
use Magento\PageCache\Model\Cache\Type as PageCacheType;
use Magento\TestFramework\App\State as AppState;
use Magento\TestFramework\TestCase\AbstractController;

/**
 * @magentoDbIsolation disabled
 * @magentoAppIsolation enabled
 */
class ProductTest extends AbstractController
{
    /**
     * @var AppState
     */
    private $appState;

    /**
     * @var string
     */
    private $currentAppMode;

    /**
     * @var CacheStateInterface
     */
    private $cacheState;

    /**
     * @var bool
     */
    private $isFullPageCacheEnabled;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->appState = $this->_objectManager->get(AppState::class);
        $this->currentAppMode = $this->appState->getMode();
        $this->cacheState = $this->_objectManager->get(CacheStateInterface::class);
        $this->isFullPageCacheEnabled = $this->cacheState->isEnabled(PageCacheType::TYPE_IDENTIFIER);

        $this->productRepository = $this->_objectManager->get(ProductRepositoryInterface::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->appState->setMode($this->currentAppMode);
        $this->cacheState->setEnabled(PageCacheType::TYPE_IDENTIFIER, $this->isFullPageCacheEnabled);
    }

    /**
     * @magentoDataFixture Magento/Catalog/controllers/_files/products.php
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/grant_catalog_category_view 1
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/grant_catalog_product_price 0
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/grant_checkout_items 0
     */
    public function testViewActionWithoutPriceAndCart()
    {
        $product = $this->productRepository->get('simple_product_1');
        $productId = $product->getId();
        $this->dispatch('catalog/product/view/id/' . $productId);

        /** @var $currentProduct \Magento\Catalog\Model\Product */
        $currentProduct = $this->_objectManager->get(\Magento\Framework\Registry::class)->registry('current_product');
        $this->assertInstanceOf(\Magento\Catalog\Model\Product::class, $currentProduct);
        $this->assertEquals($productId, $currentProduct->getId());

        $lastViewedProductId = $this->_objectManager->get(
            \Magento\Catalog\Model\Session::class
        )->getLastViewedProductId();
        $this->assertEquals($productId, $lastViewedProductId);

        $responseBody = $this->getResponse()->getBody();
        //Escape
        preg_replace('/<script\b[^>]*>\b(?:)<\\/script>/s', '', $responseBody);
        /* Product info */
        $this->assertStringContainsString('Simple Product 1 Name', $responseBody);
        $this->assertStringContainsString('Simple Product 1 Full Description', $responseBody);
        $this->assertStringContainsString('Simple Product 1 Short Description', $responseBody);
        $responseBody = preg_replace("/<script.*<\\/script>/", "", $responseBody);
        /* Stock info */
        $this->assertStringContainsString('In stock', $responseBody);
        $this->assertStringNotContainsString('Add to Cart', $responseBody);
    }

    /**
     * @magentoAppArea frontend
     * @magentoDataFixture Magento/CatalogPermissions/_files/category_products.php
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/grant_catalog_category_view 1
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/grant_catalog_product_price 0
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/grant_checkout_items 0
     * @return string
     */
    public function testFirstOpenProductPage(): string
    {
        $this->appState->setMode(AppState::MODE_DEVELOPER);
        $this->cacheState->setEnabled(PageCacheType::TYPE_IDENTIFIER, true);
        $pageCacheType = $this->_objectManager->get(PageCacheType::class);
        $pageCacheType->clean();

        $product = $this->productRepository->get('simple333');
        $productUri = sprintf('catalog/product/view/id/%d', $product->getId());
        $this->dispatch($productUri);
        $response = $this->getResponse();
        $cacheDebugHeader = $response->getHeader('X-Magento-Cache-Debug');
        $this->assertNotEmpty($cacheDebugHeader);
        $this->assertEquals('MISS', $cacheDebugHeader->getFieldValue());

        return $productUri;
    }

    /**
     * @depends testFirstOpenProductPage
     * @magentoAppArea frontend
     * @magentoDataFixture Magento/CatalogPermissions/_files/category_products.php
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/grant_catalog_category_view 1
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/grant_catalog_product_price 0
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/grant_checkout_items 0
     * @param string $productUri
     */
    public function testSecondOpenProductPage(string $productUri)
    {
        $this->appState->setMode(AppState::MODE_DEVELOPER);
        $this->cacheState->setEnabled(PageCacheType::TYPE_IDENTIFIER, true);

        $request = $this->getRequest();
        $request->setDispatched(false);
        $request->setRequestUri($productUri);

        $pageCacheKernel = $this->_objectManager->get(PageCacheKernel::class);
        $response = $pageCacheKernel->load();
        $this->assertNotEmpty($response);
    }

    /**
     * @magentoAppArea frontend
     * @magentoDataFixture Magento/CatalogPermissions/_files/category_products.php
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/grant_catalog_category_view 1
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/grant_catalog_product_price 0
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/grant_checkout_items 0
     * @return string
     */
    public function testFirstOpenProductPageWithCategory(): string
    {
        $this->appState->setMode(AppState::MODE_DEVELOPER);
        $this->cacheState->setEnabled(PageCacheType::TYPE_IDENTIFIER, true);
        $pageCacheType = $this->_objectManager->get(PageCacheType::class);
        $pageCacheType->clean();

        $product = $this->productRepository->get('simple333');
        $categoryId = 4;
        $productUri = sprintf('catalog/product/view/id/%d/category/%d', $product->getId(), $categoryId);
        $this->dispatch($productUri);
        $response = $this->getResponse();
        $cacheDebugHeader = $response->getHeader('X-Magento-Cache-Debug');
        $this->assertNotEmpty($cacheDebugHeader);
        $this->assertEquals('MISS', $cacheDebugHeader->getFieldValue());

        return $productUri;
    }

    /**
     * @depends testFirstOpenProductPageWithCategory
     * @magentoAppArea frontend
     * @magentoDataFixture Magento/CatalogPermissions/_files/category_products.php
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/grant_catalog_category_view 1
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/grant_catalog_product_price 0
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/grant_checkout_items 0
     * @param string $productUri
     */
    public function testSecondOpenProductPageWithCategory(string $productUri)
    {
        $this->appState->setMode(AppState::MODE_DEVELOPER);
        $this->cacheState->setEnabled(PageCacheType::TYPE_IDENTIFIER, true);

        $request = $this->getRequest();
        $request->setDispatched(false);
        $request->setRequestUri($productUri);

        $pageCacheKernel = $this->_objectManager->get(PageCacheKernel::class);
        $response = $pageCacheKernel->load();
        $this->assertNotEmpty($response);
    }
}
