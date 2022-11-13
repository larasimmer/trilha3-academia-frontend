<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VisualMerchandiser\Model;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use Magento\VisualMerchandiser\Model\Category\Products;
use Magento\VisualMerchandiser\Model\Position\Cache;

/**
 * Class RulesTest to verify category collection with applied conditions
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation disabled
 */
class RulesTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var string
     */
    private $positionCacheKey;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->positionCacheKey = 'position-cache-key';
    }

    /**
     * Test save positions with applied conditions
     *
     * @magentoDataFixture Magento/Store/_files/second_website_with_two_stores.php
     * @magentoDataFixture Magento/Catalog/_files/category.php
     * @magentoDataFixture Magento/VisualMerchandiser/Block/Adminhtml/Category/Merchandiser/_files/products_with_websites_and_stores.php
     */
    public function testSavePositions()
    {
        $categoryId = 333;
        $categoryRepository = $this->objectManager->get(CategoryRepositoryInterface::class);
        $category = $categoryRepository->get($categoryId);

        /** @var Rules $rulesModel */
        $rulesModel = $this->objectManager->create(Rules::class);
        $conditions = [
            [
                'attribute' => 'price',
                'operator' => 'lt',
                'value' => 100,
                'logic' => 'OR'
            ]
        ];

        /** @var $serializer Json */
        $serializer = $this->objectManager->get(Json::class);
        $serializedConditions = $serializer->serialize($conditions);

        $rule = $rulesModel->loadByCategory($category);
        $rule->setData([
            'category_id' => $categoryId,
            'is_active' => 1,
            'conditions_serialized' => $serializedConditions
        ]);
        $rule->save();
        $categoryRepository->save($category);

        $productsModel = $this->objectManager->get(Products::class);
        $productsModel->setCacheKey($this->positionCacheKey);
        $collection = $productsModel->getCollectionForGrid($categoryId);
        $productIds = [];
        foreach ($collection as $item) {
            $productIds[] = $item->getId();
        }
        shuffle($productIds);

        /** @var Cache $positionCache */
        $positionCache = $this->objectManager->get(Cache::class);
        $positionCache->saveData($this->positionCacheKey, array_flip($productIds));
        $collection = $productsModel->getCollectionForGrid($categoryId);
        $productsModel->savePositions($collection);
        $cachedPositions = $positionCache->getPositions($this->positionCacheKey);
        $this->assertEquals($productIds, array_keys($cachedPositions), 'Positions are not saved.');
    }
}
