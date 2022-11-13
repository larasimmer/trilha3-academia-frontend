<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogPermissions\Controller;

use Magento\CatalogPermissions\Model\Permission;
use Magento\Framework\App\Cache\StateInterface as CacheStateInterface;
use Magento\Framework\App\PageCache\Kernel as PageCacheKernel;
use Magento\PageCache\Model\Cache\Type as PageCacheType;
use Magento\TestFramework\App\State as AppState;
use Magento\TestFramework\TestCase\AbstractController;

/**
 * @magentoDbIsolation disabled
 * @magentoAppIsolation enabled
 */
class CategoryTest extends AbstractController
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
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->appState = $this->_objectManager->get(AppState::class);
        $this->currentAppMode = $this->appState->getMode();
        $this->cacheState = $this->_objectManager->get(CacheStateInterface::class);
        $this->isFullPageCacheEnabled = $this->cacheState->isEnabled(PageCacheType::TYPE_IDENTIFIER);
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
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/grant_catalog_category_view 1
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/grant_catalog_product_price 0
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/grant_checkout_items 0
     * @magentoDataFixture Magento/CatalogPermissions/_files/category_products.php
     */
    public function testCategoryFilterPriceNotExistsWithGlobalPriceDenied()
    {
        /** @var \Magento\Framework\Module\ModuleList $modules */
        $modules = $this->_objectManager->get(\Magento\Framework\Module\ModuleList::class);
        if (empty($modules->getOne('Magento_LayeredNavigation'))) {
            $this->markTestSkipped('Skipping test, required module Magento_LayeredNavigation is disabled.');
        }

        /** @var  $indexer \Magento\Framework\Indexer\IndexerInterface */
        $indexer = $this->_objectManager->create(\Magento\Indexer\Model\Indexer::class);
        $indexer->load(\Magento\CatalogPermissions\Model\Indexer\Category::INDEXER_ID);
        $indexer->reindexAll();
        $this->dispatch("catalog/category/view/id/4");
        $responseBody = $this->getResponse()->getBody();
        $this->assertStringNotContainsString(
            'catalog/category/view/id/4/?price=100-200',
            $responseBody,
            'Category page should not contain price filter link'
        );
    }

    /**
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/grant_catalog_category_view 1
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/grant_catalog_product_price 1
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/grant_checkout_items 0
     * @magentoDataFixture Magento/CatalogPermissions/_files/category_products.php
     */
    public function testCategoryFilterPriceShownWithGlobalPriceAllowed()
    {
        /** @var \Magento\Framework\Module\ModuleList $modules */
        $modules = $this->_objectManager->get(\Magento\Framework\Module\ModuleList::class);
        if (empty($modules->getOne('Magento_LayeredNavigation'))) {
            $this->markTestSkipped('Skipping test, required module Magento_LayeredNavigation is disabled.');
        }

        /** @var  $indexer \Magento\Framework\Indexer\IndexerInterface */
        $indexer = $this->_objectManager->create(\Magento\Indexer\Model\Indexer::class);
        $indexer->load(\Magento\CatalogPermissions\Model\Indexer\Category::INDEXER_ID);
        $indexer->reindexAll();
        $this->dispatch("catalog/category/view/id/4");
        $responseBody = $this->getResponse()->getBody();
        $this->assertStringContainsString(
            'catalog/category/view/id/4/?price=100-200',
            $responseBody,
            'Expected price filter links are absent on category page'
        );
        $this->assertStringContainsString(
            'catalog/category/view/id/4/?price=200-',
            $responseBody,
            'Expected price filter links are absent on category page'
        );
    }

    /**
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/grant_catalog_category_view 1
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/grant_catalog_product_price 1
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/grant_checkout_items 1
     * @magentoDataFixture Magento/CatalogPermissions/_files/category_products.php
     */
    public function testCategoryFilterPriceNotExists()
    {
        /** @var \Magento\Framework\Module\ModuleList $modules */
        $modules = $this->_objectManager->get(\Magento\Framework\Module\ModuleList::class);
        if (empty($modules->getOne('Magento_LayeredNavigation'))) {
            $this->markTestSkipped('Skipping test, required module Magento_LayeredNavigation is disabled.');
        }

        /** @var $permission Permission */
        $permission = $this->_objectManager->create(\Magento\CatalogPermissions\Model\Permission::class);
        $websiteId = $this->_objectManager->get(\Magento\Store\Model\StoreManagerInterface::class)
            ->getWebsite()->getId();
        $permission->setWebsiteId($websiteId)
            ->setCategoryId(4)
            ->setCustomerGroupId(null)
            ->setGrantCatalogCategoryView(Permission::PERMISSION_ALLOW)
            ->setGrantCatalogProductPrice(Permission::PERMISSION_DENY)
            ->setGrantCheckoutItems(Permission::PERMISSION_DENY)
            ->save();
        /** @var  $indexer \Magento\Framework\Indexer\IndexerInterface */
        $indexer = $this->_objectManager->create(\Magento\Indexer\Model\Indexer::class);
        $indexer->load(\Magento\CatalogPermissions\Model\Indexer\Category::INDEXER_ID);
        $indexer->reindexAll();
        $this->dispatch("catalog/category/view/id/4");
        $responseBody = $this->getResponse()->getBody();
        $this->assertStringNotContainsString(
            'catalog/category/view/id/4/?price=100-200',
            $responseBody,
            'Category page should not contain price filter link'
        );
    }

    /**
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/grant_catalog_category_view 1
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/grant_catalog_product_price 1
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/grant_checkout_items 1
     * @magentoDataFixture Magento/CatalogPermissions/_files/category_products.php
     */
    public function testCategoryFilterPricePresent()
    {
        /** @var \Magento\Framework\Module\ModuleList $modules */
        $modules = $this->_objectManager->get(\Magento\Framework\Module\ModuleList::class);
        if (empty($modules->getOne('Magento_LayeredNavigation'))) {
            $this->markTestSkipped('Skipping test, required module Magento_LayeredNavigation is disabled.');
        }

        /** @var $permission Permission */
        $permission = $this->_objectManager->create(\Magento\CatalogPermissions\Model\Permission::class);
        $websiteId = $this->_objectManager->get(\Magento\Store\Model\StoreManagerInterface::class)
            ->getWebsite()->getId();
        $permission->setWebsiteId($websiteId)
            ->setCategoryId(4)
            ->setCustomerGroupId(null)
            ->setGrantCatalogCategoryView(Permission::PERMISSION_ALLOW)
            ->setGrantCatalogProductPrice(Permission::PERMISSION_ALLOW)
            ->setGrantCheckoutItems(Permission::PERMISSION_ALLOW)
            ->save();
        /** @var  $indexer \Magento\Framework\Indexer\IndexerInterface */
        $indexer = $this->_objectManager->create(\Magento\Indexer\Model\Indexer::class);
        $indexer->load(\Magento\CatalogPermissions\Model\Indexer\Category::INDEXER_ID);
        $indexer->reindexAll();
        $this->dispatch("catalog/category/view/id/4");
        $responseBody = $this->getResponse()->getBody();
        $this->assertStringContainsString(
            'catalog/category/view/id/4/?price=100-200',
            $responseBody,
            'Expected price filter links are absent on category page'
        );
        $this->assertStringContainsString(
            'catalog/category/view/id/4/?price=200-',
            $responseBody,
            'Expected price filter links are absent on category page'
        );
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
    public function testFirstOpenCategoryPage(): string
    {
        $this->appState->setMode(AppState::MODE_DEVELOPER);
        $this->cacheState->setEnabled(PageCacheType::TYPE_IDENTIFIER, true);
        $pageCacheType = $this->_objectManager->get(PageCacheType::class);
        $pageCacheType->clean();

        $categoryId = 4;
        $categoryUri = sprintf('catalog/category/view/id/%d', $categoryId);
        $this->dispatch($categoryUri);
        $response = $this->getResponse();
        $cacheDebugHeader = $response->getHeader('X-Magento-Cache-Debug');
        $this->assertNotEmpty($cacheDebugHeader);
        $this->assertEquals('MISS', $cacheDebugHeader->getFieldValue());

        return $categoryUri;
    }

    /**
     * @depends testFirstOpenCategoryPage
     * @magentoAppArea frontend
     * @magentoDataFixture Magento/CatalogPermissions/_files/category_products.php
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/grant_catalog_category_view 1
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/grant_catalog_product_price 0
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/grant_checkout_items 0
     * @param string $categoryUri
     */
    public function testSecondOpenCategoryPage(string $categoryUri)
    {
        $this->appState->setMode(AppState::MODE_DEVELOPER);
        $this->cacheState->setEnabled(PageCacheType::TYPE_IDENTIFIER, true);

        $request = $this->getRequest();
        $request->setDispatched(false);
        $request->setRequestUri($categoryUri);

        $pageCacheKernel = $this->_objectManager->get(PageCacheKernel::class);
        $response = $pageCacheKernel->load();
        $this->assertNotEmpty($response);
    }
}
