<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);
namespace Magento\VisualMerchandiser\Model\Category;

/**
 * @magentoAppArea adminhtml
 * @magentoDbIsolation disabled
 */
class ProductsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\TestFramework\ObjectManager
     */
    private $objectManager;

    /**
     * @var string
     */
    private $positionCacheKey;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $this->positionCacheKey = 'position-cache-key';
    }

    /**
     * @magentoDataFixture Magento/Store/_files/second_website_with_two_stores.php
     * @magentoDataFixture Magento/Catalog/_files/category.php
     * @magentoDataFixture Magento/VisualMerchandiser/Block/Adminhtml/Category/Merchandiser/_files/products_with_websites_and_stores.php
     */
    public function testSavePositions()
    {
        $categoryId = 333;
        /** @var \Magento\VisualMerchandiser\Model\Category\Products $productsModel */
        $productsModel = $this->objectManager->get(\Magento\VisualMerchandiser\Model\Category\Products::class);
        $productsModel->setCacheKey($this->positionCacheKey);
        $collection = $productsModel->getCollectionForGrid($categoryId);
        /** @var \Magento\VisualMerchandiser\Model\Position\Cache $positionCache */
        $positionCache = $this->objectManager->get(\Magento\VisualMerchandiser\Model\Position\Cache::class);

        $productIds = [];
        foreach ($collection as $item) {
            $productIds[] = $item->getId();
        }
        $productsModel->savePositions($collection);
        $cachedPositions = $positionCache->getPositions($this->positionCacheKey);
        $this->assertEquals($productIds, array_keys($cachedPositions), 'Positions are incorrect.');

        shuffle($productIds);
        $positionCache->saveData($this->positionCacheKey, array_flip($productIds));
        $collection = $productsModel->getCollectionForGrid($categoryId);
        $productsModel->savePositions($collection);
        $cachedPositions = $positionCache->getPositions($this->positionCacheKey);
        $this->assertEquals($productIds, array_keys($cachedPositions), 'Positions are not saved.');

        /** @var \Magento\VisualMerchandiser\Model\Sorting $sorting */
        $sorting = $this->objectManager->create(\Magento\VisualMerchandiser\Model\Sorting::class);
        $sortOption = 8; //Name\Descending
        $sortInstance = $sorting->getSortingInstance($sortOption);
        $sortedCollection = $sortInstance->sort($collection);
        $sortedCollection->clear();
        $sortedProductIds = [];
        foreach ($sortedCollection as $item) {
            $sortedProductIds[] = $item->getId();
        }
        $positionCache->saveData($this->positionCacheKey, array_flip($productIds), $sortOption);
        $collection = $productsModel->getCollectionForGrid($categoryId);
        $productsModel->savePositions($collection);
        $cachedPositions = $positionCache->getPositions($this->positionCacheKey);
        $this->assertEquals($sortedProductIds, array_keys($cachedPositions), 'Products are not sorted.');
    }
}
