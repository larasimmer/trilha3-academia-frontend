<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Magento\VisualMerchandiser\Controller\Adminhtml\Category;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * Grid controller test.
 *
 * @magentoAppArea adminhtml
 */
class AbstractGridTest extends AbstractBackendController
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->productRepository = Bootstrap::getObjectManager()->get(ProductRepositoryInterface::class);
        $this->storeManager = Bootstrap::getObjectManager()->get(StoreManagerInterface::class);
    }

    /**
     * Check that grid action response consists correct currency and prices
     *
     * @magentoDbIsolation disabled
     * @magentoDataFixture Magento/Catalog/_files/products_with_websites_and_stores.php
     * @magentoDataFixture Magento/Catalog/_files/category.php
     * @magentoConfigFixture fixture_second_store_store currency/options/base UAH
     * @magentoConfigFixture fixture_second_store_store currency/options/allow UAH
     * @magentoConfigFixture fixture_second_store_store catalog/price/scope 1
     */
    public function testGridAction()
    {
        $store = $this->storeManager->getStore('fixture_second_store');
        $product = $this->productRepository->get('simple-2');
        $product->setCategoryIds([333]);
        $this->productRepository->save($product);
        $this->getRequest()
            ->setPostValue(['position_cache_key' => 'cache-key'])
            ->setParams(
                [
                    'id' => 333,
                    'store' => $store->getId(),
                ]
            )
            ->setMethod(HttpRequest::METHOD_POST);
        $this->dispatch("backend/merchandiser/category/grid/");
        $content = $this->getResponse()->getContent();
        $this->assertStringContainsString(' ₴10.00', $content);
    }
}
