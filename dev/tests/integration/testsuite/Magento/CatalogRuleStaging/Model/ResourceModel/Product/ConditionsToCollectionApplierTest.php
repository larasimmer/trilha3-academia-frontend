<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogRuleStaging\Model\ResourceModel\Product;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogRule\Model\ResourceModel\Product\ConditionsToCollectionApplier;
use Magento\CatalogRule\Model\Rule\Condition\Combine;
use Magento\CatalogRule\Model\Rule\Condition\CombineFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * Conditions to collection applier staging update cases test class
 */
class ConditionsToCollectionApplierTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var CollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var ConditionsToCollectionApplier
     */
    private $conditionsToCollectionApplier;

    /**
     * @var CombineFactory
     */
    private $combinedConditionFactory;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepositoryInterface;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->productCollectionFactory = $this->objectManager->get(CollectionFactory::class);
        $this->productRepositoryInterface = $this->objectManager->get(ProductRepositoryInterface::class);
        $this->conditionsToCollectionApplier = $this->objectManager->get(ConditionsToCollectionApplier::class);
        $this->combinedConditionFactory = $this->objectManager->get(CombineFactory::class);
    }

    /**
     * Test if collection is filtered correctly with is_null filter in case if product updated by staging
     *
     * @magentoDataFixture Magento/CatalogStaging/_files/product_simple_with_custom_attribute_and_staging.php
     * @magentoDataFixture Magento/CatalogStaging/_files/simple_product_staged_changes_with_entity_id_not_equal_to_row_id.php
     * @return void
     */
    public function testApplyFilterConditionsToCollectionTest(): void
    {
        $product = $this->productRepositoryInterface->get('asimpleproduct');
        $this->assertNotEquals(
            $product->getid(),
            $product->getRowId(),
            'Preconditions does not satisfy test asserts'
        );
        $productCollection = $this->productCollectionFactory->create();
        $resultCollection = $this->conditionsToCollectionApplier->applyConditionsToCollection(
            $this->getCombineCondition(),
            $productCollection
        );

        $resultSkuList = array_map(
            function (Product $product) {
                return $product->getSku();
            },
            array_values($resultCollection->getItems())
        );

        $this->assertCount(1, $resultSkuList);
        $this->assertEquals('asimpleproduct', reset($resultSkuList));
    }

    /**
     * Return combine conditions for filtering
     *
     * @return Combine
     */
    private function getCombineCondition(): Combine
    {
        $conditions = [
            'type' => Combine::class,
            'aggregator' => 'all',
            'value' => '1',
            'conditions' => [
                [
                    'type' => \Magento\CatalogRule\Model\Rule\Condition\Product::class,
                    'attribute' => 'attribute_code_custom',
                    'operator' => '<=>',
                    'value' => "1",
                    'is_value_parsed' => false,
                ]
            ],
        ];
        $combinedCondition = $this->combinedConditionFactory->create();
        $combinedCondition->setPrefix('conditions');
        $combinedCondition->loadArray($conditions);

        return $combinedCondition;
    }
}
