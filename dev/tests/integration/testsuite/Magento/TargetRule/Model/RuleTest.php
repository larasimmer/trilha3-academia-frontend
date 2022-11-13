<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\TargetRule\Model;

class RuleTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\TargetRule\Model\Rule
     */
    protected $_model;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->_model = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
            \Magento\TargetRule\Model\Rule::class
        );
    }

    /**
     * Test empty rules
     */
    public function testValidateDataOnEmpty()
    {
        $data = new \Magento\Framework\DataObject();
        $this->assertTrue($this->_model->validateData($data), 'True for empty object');
    }

    /**
     * Test valid rule
     */
    public function testValidateDataOnValid()
    {
        $data = new \Magento\Framework\DataObject();
        $data->setRule(
            ['actions' => ['test' => ['type' => \Magento\TargetRule\Model\Actions\Condition\Combine::class]]]
        );

        $this->assertTrue($this->_model->validateData($data), 'True for right data');
    }

    /**
     * Test invalid rule
     *
     * @param string $code
     * @dataProvider invalidCodesDataProvider
     */
    public function testValidateDataOnInvalidCode($code)
    {
        $data = new \Magento\Framework\DataObject();
        $data->setRule(
            [
                'actions' => [
                    'test' => [
                        'type' => \Magento\TargetRule\Model\Actions\Condition\Combine::class,
                        'attribute' => $code,
                    ],
                ],
            ]
        );
        $this->assertCount(1, $this->_model->validateData($data), 'Error for invalid attribute code');
    }

    /**
     * @return array
     */
    public static function invalidCodesDataProvider()
    {
        return [[''], ['_'], ['123'], ['!'], [str_repeat('2', 256)]];
    }

    /**
     * Test invalid rule type
     *
     */
    public function testValidateDataOnInvalidType()
    {
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);

        $data = new \Magento\Framework\DataObject();
        $data->setRule(['actions' => ['test' => ['type' => 'Magento\TargetRule\Invalid']]]);
        $this->_model->validateData($data);
    }

    /**
     * Test target rules with category rule conditions
     *
     * @param array $products
     * @param array $conditions
     * @param array $expectedProducts
     * @magentoDataFixture Magento/Catalog/_files/categories_no_products.php
     * @magentoDataFixture Magento/Catalog/_files/second_product_simple.php
     * @magentoDataFixture Magento/TargetRule/_files/products.php
     * @magentoDataFixture Magento/TargetRule/_files/related.php
     * @magentoAppIsolation enabled
     * @dataProvider categoryConditionDataProvider
     */
    public function testCategoryCondition(array $products, array $conditions, array $expectedProducts)
    {
        /** @var \Magento\Catalog\Api\CategoryLinkManagementInterface $categoryLinkManagement */
        /** @var \Magento\Catalog\Model\ProductRepository $productRepository */
        /** @var \Magento\CatalogInventory\Api\StockItemRepositoryInterface $stockItemRepository */
        /** @var $targetRuleModel \Magento\TargetRule\Model\Rule */
        /** @var $targetRuleIndexModel \Magento\TargetRule\Model\Index */
        $objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $categoryLinkManagement = $objectManager->get(\Magento\Catalog\Api\CategoryLinkManagementInterface::class);
        $stockItemRepository = $objectManager->get(\Magento\CatalogInventory\Api\StockItemRepositoryInterface::class);
        $productRepository = $objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
        $targetRuleIndexModel = $objectManager->create(\Magento\TargetRule\Model\Index::class);
        $targetRuleModel = $objectManager->create(\Magento\TargetRule\Model\Rule::class);
        $actualProducts = [];
        $idsToReindex = [];
        foreach ($products as $sku => $categories) {
            $categoryLinkManagement->assignProductToCategories($sku, $categories);
            /** @var \Magento\CatalogInventory\Model\Stock\Item $stockItem */
            $stockItem = $objectManager->create(\Magento\CatalogInventory\Model\Stock\Item::class);
            $product = $productRepository->get($sku);
            $stockItem->setProduct($product);
            $stockItem->load($product->getId(), 'product_id');
            $stockItem->setUseConfigManageStock(1);
            $stockItem->setQty(1000);
            $stockItem->setIsQtyDecimal(0);
            $stockItem->setIsInStock(1);
            $stockItemRepository->save($stockItem);
            $idsToReindex[] = $product->getId();
        }

        /** @var \Magento\Indexer\Model\Indexer $indexer */
        $indexer = $objectManager->create(\Magento\Indexer\Model\Indexer::class);
        $indexer->load('catalog_product_price');
        $indexer->reindexList($idsToReindex);

        $targetRuleModel->load('related', 'name');
        $data['actions'] = $conditions;
        $targetRuleModel->loadPost($data);
        $targetRuleModel->save();
        $targetRuleIndexModel->setType($targetRuleModel->getApplyTo());
        $targetRuleIndexModel->setProduct($productRepository->get('simple2'));
        foreach (array_keys($targetRuleIndexModel->getProductIds()) as $productId) {
            $actualProducts[] = $productRepository->getById($productId)->getSku();
        }
        sort($expectedProducts);
        sort($actualProducts);
        $this->assertEquals(array_values($expectedProducts), array_values($actualProducts));
    }

    /**
     * @return array
     */
    public function categoryConditionDataProvider()
    {
        return [
            'Product category does not contain 5 AND Product SKU contains "simple_product"' => [
                'products' => [
                    'simple_product_1' => [3, 4],
                    'simple_product_2' => [3, 4, 5],
                    'simple_product_3' => [3, 4, 5],
                ],
                'conditions' => [
                    '1' => [
                        'type' => 'Magento\TargetRule\Model\Actions\Condition\Combine',
                        'aggregator' => 'all',
                        'value' => '1',
                    ],
                    '1--1' => [
                        'type' => 'Magento\TargetRule\Model\Actions\Condition\Product\Attributes',
                        'attribute' => 'category_ids',
                        'operator' => '!{}',
                        'value' => 5,
                        'is_value_processed' => false,
                        'value_type' => 'constant',
                    ],
                    '1--2' => [
                        'type' => 'Magento\TargetRule\Model\Actions\Condition\Product\Attributes',
                        'attribute' => 'sku',
                        'operator' => '{}',
                        'value' => 'simple_product',
                        'is_value_processed' => false,
                        'value_type' => 'constant',
                    ],
                ],
                'expectedProducts' => [
                    'simple_product_1'
                ]
            ],
            'Product category contains 5 AND Product SKU contains "simple_product"' => [
                'products' => [
                    'simple_product_1' => [3, 4],
                    'simple_product_2' => [3, 4, 5],
                    'simple_product_3' => [3, 4, 5],
                ],
                'conditions' => [
                    '1' => [
                        'type' => 'Magento\TargetRule\Model\Actions\Condition\Combine',
                        'aggregator' => 'all',
                        'value' => '1',
                    ],
                    '1--1' => [
                        'type' => 'Magento\TargetRule\Model\Actions\Condition\Product\Attributes',
                        'attribute' => 'category_ids',
                        'operator' => '{}',
                        'value' => 5,
                        'is_value_processed' => false,
                        'value_type' => 'constant',
                    ],
                    '1--2' => [
                        'type' => 'Magento\TargetRule\Model\Actions\Condition\Product\Attributes',
                        'attribute' => 'sku',
                        'operator' => '{}',
                        'value' => 'simple_product',
                        'is_value_processed' => false,
                        'value_type' => 'constant',
                    ],
                ],
                'expectedProducts' => [
                    'simple_product_2',
                    'simple_product_3',
                ]
            ],
        ];
    }
}
