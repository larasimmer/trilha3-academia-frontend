<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\TargetRule\Model;

/**
 * Test for Magento\TargetRule\Model\Index
 */
class IndexTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var \Magento\TargetRule\Model\ResourceModel\Rule
     */
    private $resourceModel;

    protected function setUp(): void
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $this->resourceModel = $this->objectManager->get(\Magento\TargetRule\Model\ResourceModel\Rule::class);
    }

    /**
     * @magentoDbIsolation disabled
     *
     * @magentoDataFixture Magento/TargetRule/_files/products_with_attributes.php
     * @dataProvider rulesDataProvider
     *
     * @param int $ruleType
     * @param string $actionAttribute
     * @param string $valueType
     * @param string $operator
     * @param string $attributeValue
     * @param array $productsSku
     *
     * @return void
     */
    public function testGetProductIds(
        int $ruleType,
        string $actionAttribute,
        string $valueType,
        string $operator,
        string $attributeValue,
        array $productsSku
    ): void {
        /** @var \Magento\Catalog\Model\ProductRepository $productRepository */
        $productRepository = $this->objectManager->create(\Magento\Catalog\Model\ProductRepository::class);
        $product = $productRepository->get('simple1');

        $model = $this->createRuleModel($ruleType, $actionAttribute, $valueType, $operator, $attributeValue);
        /** @var \Magento\TargetRule\Model\Index $index */
        $index = $this->objectManager->create(\Magento\TargetRule\Model\Index::class)
            ->setType($ruleType)
            ->setProduct($product);
        $productIds = array_map(
            'intval',
            array_keys($index->getProductIds())
        );
        sort($productIds);
        $this->resourceModel->delete($model);

        $expectedProductIds = [];
        foreach ($productsSku as $sku) {
            $expectedProductIds[] = (int) $productRepository->get($sku)->getId();
        }
        sort($expectedProductIds);
        $this->assertEquals($expectedProductIds, $productIds);
    }

    /**
     * @return array
     */
    public function rulesDataProvider(): array
    {
        return [
            'cross sells rule by the same global attribute' => [
                \Magento\TargetRule\Model\Rule::CROSS_SELLS,
                'global_attribute',
                \Magento\TargetRule\Model\Actions\Condition\Product\Attributes::VALUE_TYPE_SAME_AS,
                '==',
                '',
                ['simple2', 'simple3', 'simple4'],
            ],
            'related rule by the same category id' => [
                \Magento\TargetRule\Model\Rule::RELATED_PRODUCTS,
                'category_ids',
                \Magento\TargetRule\Model\Actions\Condition\Product\Attributes::VALUE_TYPE_SAME_AS,
                '==',
                '',
                ['simple3'],
            ],
            'up sells rule by child of category ids' => [
                \Magento\TargetRule\Model\Rule::UP_SELLS,
                'category_ids',
                \Magento\TargetRule\Model\Actions\Condition\Product\Attributes::VALUE_TYPE_CHILD_OF,
                '==',
                '',
                ['child_simple'],
            ],
            'cross sells rule by constant category ids' => [
                \Magento\TargetRule\Model\Rule::CROSS_SELLS,
                'category_ids',
                \Magento\TargetRule\Model\Actions\Condition\Product\Attributes::VALUE_TYPE_CONSTANT,
                '==',
                '44',
                ['simple2', 'simple4'],
            ],
            'up sells rule by the same static attribute' => [
                \Magento\TargetRule\Model\Rule::UP_SELLS,
                'type_id',
                \Magento\TargetRule\Model\Actions\Condition\Product\Attributes::VALUE_TYPE_SAME_AS,
                '==',
                '',
                ['simple2', 'simple3', 'simple4', 'child_simple'],
            ],
            'related rule by constant promo attribute' => [
                \Magento\TargetRule\Model\Rule::RELATED_PRODUCTS,
                'promo_attribute',
                \Magento\TargetRule\Model\Actions\Condition\Product\Attributes::VALUE_TYPE_CONSTANT,
                '==',
                'RELATED_PRODUCT',
                ['simple2', 'simple3', 'simple4'],
            ],
            'related rule by attribute where value is equal to multiple values' => [
                \Magento\TargetRule\Model\Rule::RELATED_PRODUCTS,
                'promo_attribute',
                \Magento\TargetRule\Model\Actions\Condition\Product\Attributes::VALUE_TYPE_CONSTANT,
                '==',
                'RELATED_PRODUCT,ANOTHER_PRODUCT',
                [],
            ],
            'related rule by scoped attribute where value is one of' => [
                \Magento\TargetRule\Model\Rule::RELATED_PRODUCTS,
                'promo_attribute',
                \Magento\TargetRule\Model\Actions\Condition\Product\Attributes::VALUE_TYPE_CONSTANT,
                '()',
                'RELATED_PRODUCT,ANOTHER_PRODUCT',
                ['simple2', 'simple3', 'simple4', 'child_simple'],
            ],
            'related rule by global attribute where value is one of' => [
                \Magento\TargetRule\Model\Rule::RELATED_PRODUCTS,
                'global_attribute',
                \Magento\TargetRule\Model\Actions\Condition\Product\Attributes::VALUE_TYPE_CONSTANT,
                '()',
                '666,777',
                ['simple2', 'simple3', 'simple4', 'child_simple'],
            ],
            'related rule by static attribute where value is one of' => [
                \Magento\TargetRule\Model\Rule::RELATED_PRODUCTS,
                'sku',
                \Magento\TargetRule\Model\Actions\Condition\Product\Attributes::VALUE_TYPE_CONSTANT,
                '()',
                'simple2,child_simple',
                ['simple2', 'child_simple'],
            ],
        ];
    }

    /**
     * @param int $ruleType
     * @param string $actionAttribute
     * @param string $valueType
     * @param string $operator
     * @param string $attributeValue
     * @return \Magento\TargetRule\Model\Rule
     */
    private function createRuleModel(
        int $ruleType,
        string $actionAttribute,
        string $valueType,
        string $operator,
        string $attributeValue
    ): \Magento\TargetRule\Model\Rule {
        /** @var \Magento\TargetRule\Model\Rule $model */
        $model = $this->objectManager->create(\Magento\TargetRule\Model\Rule::class);
        $model->setName('Test rule');
        $model->setSortOrder(0);
        $model->setIsActive(1);
        $model->setApplyTo($ruleType);

        $conditions = [
            'type' => \Magento\TargetRule\Model\Actions\Condition\Combine::class,
            'aggregator' => 'all',
            'value' => 1,
            'new_child' => '',
            'conditions' => [],
        ];
        $conditions['conditions'][1] = [
            'type' => \Magento\TargetRule\Model\Rule\Condition\Product\Attributes::class,
            'attribute' => 'category_ids',
            'operator' => '==',
            'value' => 33,
        ];
        $model->getConditions()->setConditions([])->loadArray($conditions);

        $actions = [
            'type' => \Magento\TargetRule\Model\Actions\Condition\Combine::class,
            'aggregator' => 'all',
            'value' => 1,
            'new_child' => '',
            'actions' => [],
        ];
        $actions['actions'][1] = [
            'type' => \Magento\TargetRule\Model\Actions\Condition\Product\Attributes::class,
            'attribute' => $actionAttribute,
            'operator' => $operator,
            'value_type' => $valueType,
            'value' => $attributeValue,
        ];
        $model->getActions()->setActions([])->loadArray($actions, 'actions');

        $this->resourceModel->save($model);

        return $model;
    }
}
