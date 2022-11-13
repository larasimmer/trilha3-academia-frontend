<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Model\Segment\Condition\Product;

use Magento\CustomerSegment\Model\Segment\Condition\Product\Attributes as ProductAttributesCondition;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\CustomerSegment\Model\ResourceModel\Segment as ResourceModel;

/**
 * Test for @see \Magento\CustomerSegment\Model\Segment\Condition\Product\Attributes
 */
class AttributesTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ProductAttributesCondition
     */
    private $productAttributesCondition;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->productAttributesCondition = Bootstrap::getObjectManager()->create(ProductAttributesCondition::class);
    }

    /**
     * Tests subfilter.
     *
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     * @dataProvider getSubfilterSqlDataProvider
     */
    public function testGetSubfilterSql(
        $requireValid,
        $attributeName,
        $attributeValue,
        $productIds,
        $combineProductCondition,
        $expectedResult
    ) {
        $this->productAttributesCondition
            ->setAttribute($attributeName)
            ->setOperator('==')
            ->setValue($attributeValue)
            ->setProductIds($productIds)
            ->setCombineProductCondition($combineProductCondition);
        $website = new \Zend_Db_Expr(':website_id');
        $fieldName = 'item.product_id';
        $result = $this->productAttributesCondition->getSubfilterSql($fieldName, $requireValid, $website);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return array
     */
    public function getSubfilterSqlDataProvider()
    {
        $productId = 1;
        $categoryWithProductsId = 2;
        $categoryEmptyId = 100500;

        /** @var ResourceModel $resource */
        $resource = Bootstrap::getObjectManager()->create(ResourceModel::class);
        $productEntityTable = $resource->getTable('catalog_product_entity');
        $productEntityIntTable = $resource->getTable('catalog_product_entity_int');
        $storeTable = $resource->getTable('store') == 'store'
            ? '`store`'
            : "`{$resource->getTable('store')}` AS `store`";
        return [
            'Non-static attribute' => [
                true,           // $requireValid
                'color',        // $attributeName
                '58',           // $attributeValue
                [],             // $productIds
                false,          // $combineProductCondition
                "item.product_id IN (SELECT `main`.`entity_id` FROM `{$productEntityTable}` AS `main`\n"
                . " INNER JOIN `{$productEntityIntTable}` AS `eav_attribute` ON eav_attribute.row_id=main.row_id\n"
                . " INNER JOIN {$storeTable} ON eav_attribute.store_id=store.store_id"
                . " WHERE ((eav_attribute.attribute_id = '93') AND (store.website_id IN (0, :website_id))"
                . " AND (eav_attribute.value = '58')) AND (main.created_in <= 1) AND (main.updated_in > 1))"
            ],
            'Category attribute if category contains product' => [
                false,          // $requireValid
                'category_ids', // $attributeName
                $categoryWithProductsId, // $attributeValue
                [],             // $productIds
                false,          // $combineProductCondition
                "item.product_id NOT IN (SELECT `main`.`entity_id` FROM `{$productEntityTable}` AS `main`"
                . " WHERE ((main.entity_id IN ({$productId}))) AND (main.created_in <= 1) AND (main.updated_in > 1))"
            ],
            'Category attribute if category is empty' => [
                false,          // $requireValid
                'category_ids', // $attributeName
                $categoryEmptyId, // $attributeValue
                [],             // $productIds
                false,          // $combineProductCondition
                "item.product_id NOT IN (SELECT `main`.`entity_id` FROM `{$productEntityTable}` AS `main`"
                . " WHERE ((FALSE)) AND (main.created_in <= 1) AND (main.updated_in > 1))"
            ],
            'Static attribute; Inverted condition' => [
                true,           // $requireValid
                'sku',          // $attributeName
                'test-sku',     // $attributeValue
                [],             // $productIds
                false,          // $combineProductCondition
                "item.product_id IN (SELECT `main`.`entity_id` FROM `{$productEntityTable}` AS `main` "
                . "WHERE ((main.sku = 'test-sku')) AND (main.created_in <= 1) AND (main.updated_in > 1))"
            ],
            'Specified product ids; combine product condition' => [
                false,          // $requireValid
                'sku',          // $attributeName
                'test-sku',     // $attributeValue
                [12, 24],       // $productIds
                true,           // $combineProductCondition
                "item.product_id IN (SELECT `main`.`entity_id` FROM `{$productEntityTable}` AS `main` "
                . "WHERE ((main.sku = 'test-sku') AND (main.entity_id IN (12, 24))) AND (main.created_in <= 1) "
                . "AND (main.updated_in > 1))"
            ],
        ];
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/quote_with_customer.php
     */
    public function testGetSatisfiedIds()
    {
        $this->productAttributesCondition
            ->setAttribute('name')
            ->setOperator('==')
            ->setValue('Simple Product')
            ->setProductIds([])
            ->setCombineProductCondition(false);
        $websiteId = '1';
        $result = $this->productAttributesCondition->getSatisfiedIds($websiteId);
        $this->assertCount(1, $result);
        /** @var CartRepositoryInterface $cartRepository */
        $cartRepository = Bootstrap::getObjectManager()->create(CartRepositoryInterface::class);
        $fixtureCustomerId = 1;
        $customerCart = $cartRepository->getForCustomer($fixtureCustomerId);
        $this->assertEquals($customerCart->getId(), $result[0]);
    }

    /**
     * Tests IsSatisfiedBy method.
     *
     * @magentoDataFixture Magento/Sales/_files/quote_with_customer.php
     */
    public function testIsSatisfiedBy()
    {
        $this->productAttributesCondition
            ->setAttribute('name')
            ->setOperator('==')
            ->setValue('Simple Product')
            ->setProductIds([])
            ->setCombineProductCondition(false);
        $websiteId = '1';
        $fixtureProductId = 1;
        $matchingParams['quote_item']['product_id'] = $fixtureProductId;
        $this->assertTrue($this->productAttributesCondition->isSatisfiedBy(null, $websiteId, $matchingParams));
        $notMatchingParams['quote_item']['product_id'] = 111111;
        $this->assertFalse($this->productAttributesCondition->isSatisfiedBy(null, $websiteId, $notMatchingParams));
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     * @param string $sku
     * @param bool $include
     * @param string $result
     * @throws \Magento\Framework\Exception\LocalizedException
     * @dataProvider getSubfilterSqlInStatementDataProvider
     */
    public function testGetSubfilterSqlInStatement(
        string $sku,
        bool $include,
        string $result
    ) {
        $objectManager = Bootstrap::getObjectManager();
        /** @var StoreManagerInterface $storeManager */
        $storeManager = $objectManager->get(StoreManagerInterface::class);
        $websiteId = $storeManager->getWebsite()->getId();
        $this->productAttributesCondition
            ->setAttribute('sku')
            ->setOperator('==')
            ->setValue($sku)
            ->setUseSelect(false)
            ->setCombineProductCondition(false);
        $fieldName = 'item.product_id';
        $this->assertEquals(
            $result,
            $this->productAttributesCondition->getSubfilterSql($fieldName, $include, $websiteId)
        );
    }

    /**
     * @return array
     */
    public function getSubfilterSqlInStatementDataProvider(): array
    {
        return [
            [
                'sku' => 'simple',
                'include' => true,
                'result' => 'item.product_id IN (1)'
            ],
            [
                'sku' => 'simple',
                'include' => false,
                'result' => 'item.product_id NOT IN (1)'
            ],
            [
                'sku' => 'simple001',
                'include' => true,
                'result' => '1=0'
            ],
            [
                'sku' => 'simple001',
                'include' => false,
                'result' => '1=1'
            ]
        ];
    }
}
