<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissions\Observer;

use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogPermissions\Model\Indexer\Product\Processor as ProductIndexerProcessor;
use Magento\Checkout\Model\Cart;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * @magentoAppArea frontend
 */
class CheckQuotePermissionsObserverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var CategoryLinkManagementInterface
     */
    private $linkManagement;

    /**
     * @var ProductIndexerProcessor
     */
    private $productIndexerProcessor;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->productRepository = Bootstrap::getObjectManager()->get(ProductRepositoryInterface::class);
        $this->linkManagement = Bootstrap::getObjectManager()->get(CategoryLinkManagementInterface::class);
        $this->productIndexerProcessor = Bootstrap::getObjectManager()->get(ProductIndexerProcessor::class);
    }

    /**
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/enabled 1
     * @magentoDataFixture Magento/CatalogPermissions/_files/categories_with_permission.php
     * @magentoDataFixture Magento/Catalog/_files/second_product_simple.php
     * @magentoDataFixture Magento/GroupedProduct/_files/product_grouped_with_simple.php
     * @dataProvider productDataProvider
     * @param string $sku
     * @param array $requestInfo
     */
    public function testExecute(string $sku, array $requestInfo)
    {
        $this->assignProductToCategory($sku, 3);
        $product = $this->productRepository->get($sku, false, 1, true);

        $cart = Bootstrap::getObjectManager()->create(Cart::class);
        $cart->addProduct($product, $requestInfo);
        $cart->save();
        $errors = $cart->getQuote()->getErrors();
        $this->assertEmpty($errors);
    }

    /**
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/enabled 1
     * @magentoDataFixture Magento/CatalogPermissions/_files/categories_with_permission.php
     * @magentoDataFixture Magento/Catalog/_files/second_product_simple.php
     * @magentoDataFixture Magento/GroupedProduct/_files/product_grouped_with_simple.php
     * @dataProvider productDataProvider
     * @param string $sku
     * @param array $requestInfo
     */
    public function testExecuteWithErrors(string $sku, array $requestInfo)
    {
        $this->assignProductToCategory($sku, 4);
        $product = $this->productRepository->get($sku, false, 1, true);

        $cart = Bootstrap::getObjectManager()->create(Cart::class);
        $cart->addProduct($product, $requestInfo);
        $cart->save();
        $errors = $cart->getQuote()->getErrors();
        $this->assertNotEmpty($errors);
    }

    /**
     * @return array
     */
    public function productDataProvider(): array
    {
        return [
            'simple product' => [
                'simple2',
                [
                    'qty' => 1,
                ],
            ],
            'grouped product' => [
                'grouped',
                [
                    'super_group' => [
                        11 => 1,
                        22 => 1,
                    ],
                    'qty' => 1,
                ],
            ],
        ];
    }

    /**
     * @param string $sku
     * @param int $categoryId
     */
    private function assignProductToCategory(string $sku, int $categoryId): void
    {
        $product = $this->productRepository->get($sku, true);
        $this->linkManagement->assignProductToCategories($product->getSku(), [$categoryId]);
        $affectedIds = [$product->getId()];

        $productType = $product->getTypeInstance();
        if ($productType->isComposite($product)) {
            $associatedProducts = $productType->getAssociatedProducts($product);
            foreach ($associatedProducts as $childProduct) {
                $this->linkManagement->assignProductToCategories($childProduct->getSku(), [$categoryId]);
                $affectedIds[] = $childProduct->getId();
            }
        }

        $this->productIndexerProcessor->reindexList($affectedIds);
    }
}
