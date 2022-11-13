<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\TargetRule;

use Magento\Framework\ObjectManagerInterface;
use Magento\TargetRule\Model\Rule;
use Magento\TargetRule\Model\ResourceModel\Rule as ResourceModelRule;
use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TargetRule\Model\Rule\Condition\Product\Attributes as RuleConditionAttributes;
use Magento\TargetRule\Model\Actions\Condition\Product\Attributes as ActionsConditionAttributes;
use Magento\TargetRule\Model\Actions\Condition\Combine;

class RelatedProductRuleTest extends GraphQlAbstract
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var ResourceModelRule
     */
    private $resourceModel;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->resourceModel = $this->objectManager->get(ResourceModelRule::class);
    }

    /**
     * Checks if related products from target rule loaded
     *
     * @magentoDbIsolation disabled
     *
     * @magentoDataFixture Magento/Catalog/controllers/_files/products.php
     * @magentoDataFixture Magento/TargetRule/_files/related.php
     */
    public function testTargetRuleRelatedProduct()
    {
        $productSku = 'simple_product_1';

        $query = <<<QUERY
{
    products(filter: {sku: {eq: "{$productSku}"}})
    {
        items
        {
            related_products
            {
                sku
                name
                url_key
                description
                {
                    html
                }
                created_at
            }
        }
    }
}
QUERY;
        $response = $this->graphQlQuery($query);
        $items = $response['products']['items'];
        $this->assertEquals(1, count($items));
        $this->assertEquals('simple_product_2', $items[0]['related_products'][0]['sku']);
        $this->assertEquals('simple-product-2-name', $items[0]['related_products'][0]['url_key']);
        $this->assertContains('Simple Product 2 Full Description', $items[0]['related_products'][0]['description']);
    }

    /**
     * Checks if up-sell products from target rule loaded
     *
     * @magentoDbIsolation disabled
     *
     * @magentoDataFixture Magento/Catalog/controllers/_files/products.php
     * @magentoDataFixture Magento/TargetRule/_files/upsell.php
     */
    public function testTargetRuleProductUpsell()
    {
        $productSku = 'simple_product_1';

        $query = <<<QUERY
{
    products(filter: {sku: {eq: "{$productSku}"}})
    {
        items {
            upsell_products
            {
                sku
                name
                url_key
                description
                {
                    html
                }
                created_at
            }
        }
    }
}
QUERY;
        $response = $this->graphQlQuery($query);
        $items = $response['products']['items'];
        $this->assertEquals(1, count($items));
        $this->assertEquals('simple_product_2', $items[0]['upsell_products'][0]['sku']);
        $this->assertEquals('simple-product-2-name', $items[0]['upsell_products'][0]['url_key']);
        $this->assertContains('Simple Product 2 Full Description', $items[0]['upsell_products'][0]['description']);
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
     * @param string $attributeValue
     * @param array $productsSku
     *
     * @return void
     */
    public function testTargetRuleGetProductIds(
        int $ruleType,
        string $actionAttribute,
        string $valueType,
        string $attributeValue,
        array $productsSku
    ): void {
        $sku = 'simple1';

        $model = $this->createRuleModel($ruleType, $actionAttribute, $valueType, $attributeValue);
        $query = $this->getQuery($sku);
        $response = $this->graphQlQuery($query);

        $actualSkus = array_map(function ($product) {
            if (isset($product['sku'])) {
                return $product['sku'];
            }
        }, $response['products']['items'][0][$this->getLinkTypeKey($ruleType)]);
        $this->resourceModel->delete($model);
        $this->assertEquals(sort($productsSku), sort($actualSkus));
    }

    /**
     * Get GraphQl query
     *
     * @param int $sku
     * @return string
     */
    private function getQuery($sku): string
    {
        return <<<QUERY
{
    products(filter: {sku: {eq: "{$sku}"}})
    {
        items {
            crosssell_products
            {
                ...LinkedProduct
            }
            upsell_products
            {
                ...LinkedProduct
            }
            related_products
            {
                ...LinkedProduct
            }
        }
    }
}

fragment LinkedProduct on ProductInterface
{
    sku
    name
    url_key
    created_at

}
QUERY;
    }

    /**
     * Get type of product list
     *
     * @param $type
     * @return string
     */
    private function getLinkTypeKey($type): string
    {
        switch ($type) {
            case Rule::CROSS_SELLS:
                $key = 'crosssell_products';
                break;
            case Rule::UP_SELLS:
                $key = 'upsell_products';
                break;
            default:
                $key = 'related_products';
                break;
        }
        return $key;
    }

    /**
     * Generate target rule config data
     *
     * @return array
     */
    public function rulesDataProvider(): array
    {
        return [
            'related rule by the same category id' => [
                Rule::CROSS_SELLS,
                'category_ids',
                ActionsConditionAttributes::VALUE_TYPE_SAME_AS,
                '',
                ['simple3'],
            ],
            'cross sells rule by constant category ids' => [
                Rule::CROSS_SELLS,
                'category_ids',
                ActionsConditionAttributes::VALUE_TYPE_CONSTANT,
                '44',
                ['simple2', 'simple4'],
            ],
            'up sells rule by the same static attribute' => [
                Rule::UP_SELLS,
                'type_id',
                ActionsConditionAttributes::VALUE_TYPE_SAME_AS,
                '',
                ['simple2', 'simple3', 'simple4', 'child_simple'],
            ],
            'related rule by constant promo attribute' => [
                Rule::RELATED_PRODUCTS,
                'promo_attribute',
                ActionsConditionAttributes::VALUE_TYPE_CONSTANT,
                'RELATED_PRODUCT',
                ['simple2', 'simple3', 'simple4'],
            ]
        ];
    }

    /**
     * Instantiate target rule model
     *
     * @param int $ruleType
     * @param string $actionAttribute
     * @param string $valueType
     * @param string $attributeValue
     *
     * @return Rule
     */
    private function createRuleModel(
        int $ruleType,
        string $actionAttribute,
        string $valueType,
        string $attributeValue
    ): Rule {
        /** @var Rule $model */
        $model = $this->objectManager->create(Rule::class);
        $model->setName('Test rule');
        $model->setSortOrder(0);
        $model->setIsActive(1);
        $model->setApplyTo($ruleType);

        $conditions = [
            'type' => Combine::class,
            'aggregator' => 'all',
            'value' => 1,
            'new_child' => '',
            'conditions' => [],
        ];
        $conditions['conditions'][1] = [
            'type' => RuleConditionAttributes::class,
            'attribute' => 'category_ids',
            'operator' => '==',
            'value' => 33,
        ];
        $model->getConditions()->setConditions([])->loadArray($conditions);

        $actions = [
            'type' => Combine::class,
            'aggregator' => 'all',
            'value' => 1,
            'new_child' => '',
            'actions' => [],
        ];
        $actions['actions'][1] = [
            'type' => ActionsConditionAttributes::class,
            'attribute' => $actionAttribute,
            'operator' => '==',
            'value_type' => $valueType,
            'value' => $attributeValue,
        ];
        $model->getActions()->setActions([])->loadArray($actions, 'actions');

        $this->resourceModel->save($model);

        return $model;
    }
}
