<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\TargetRule\Controller\Catalog;

class ProductTest extends \Magento\TestFramework\TestCase\AbstractController
{
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product
     */
    protected $productResource;

    /**
     * Bootstrap application before any test
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->productResource = $this->_objectManager->create(\Magento\Catalog\Model\ResourceModel\Product::class);
    }

    /**
     * Covers Magento/TargetRule/view/frontend/catalog/product/list/related.html
     * Checks if related products are displayed
     *
     * @magentoDbIsolation disabled
     *
     * @magentoDataFixture Magento/Catalog/controllers/_files/products.php
     * @magentoDataFixture Magento/TargetRule/_files/related.php
     */
    public function testProductViewActionRelated()
    {
        $productId = $this->productResource->getIdBySku('simple_product_1');
        $this->dispatch('catalog/product/view/id/' . $productId);
        $content = $this->getResponse()->getBody();
        $this->assertStringContainsString('<div class="block related"', $content);
        $this->assertStringContainsString('Simple Product 2 Name', $content);
    }

    /**
     * Checks if related products are displayed and confirms that out of stock products are excluded
     *
     * Covers Magento/TargetRule/view/frontend/catalog/product/list/related.html
     *
     * 1. Define 3 products: product 1 in stock, product 2 out of stock, product 3 in stock
     * 2. Define a related product rule that returns only 1 result
     * 3. View related products for first product and confirm that:
     *      a. product 2 (out of stock) is not contained
     *      b. product 3 (in stock) is contained
     *
     * @magentoDbIsolation disabled
     *
     * @magentoDataFixture Magento/TargetRule/_files/products.php
     * @magentoDataFixture Magento/TargetRule/_files/related.php
     */
    public function testProductViewActionRelatedOutOfStock()
    {
        // Set result limit of rule to 1 according to preconditions
        /** @var \Magento\TargetRule\Model\Rule $rule */
        $rule = $this->_objectManager->create(\Magento\TargetRule\Model\Rule::class);
        /** @var \Magento\TargetRule\Model\ResourceModel\Rule $ruleResource */
        $ruleResource = $this->_objectManager->create(\Magento\TargetRule\Model\ResourceModel\Rule::class);
        $ruleResource->load($rule, 'related', 'name');
        $rule->setPositionsLimit(1);
        $ruleResource->save($rule);

        $productId = $this->productResource->getIdBySku('simple_product_1');
        $this->dispatch('catalog/product/view/id/' . $productId);
        $content = $this->getResponse()->getBody();
        $this->assertStringContainsString('<div class="block related"', $content);
        $this->assertStringNotContainsString('Simple Product 2 Name', $content);
        $this->assertStringContainsString('Simple Product 3 Name', $content);
    }

    /**
     * Checks if related products are displayed and confirms that out of stock products are included
     *
     * @magentoDbIsolation disabled
     * @magentoDataFixture Magento/TargetRule/_files/products.php
     * @magentoDataFixture Magento/TargetRule/_files/related.php
     * @magentoConfigFixture default_store cataloginventory/options/show_out_of_stock 1
     * @return void
     */
    public function testProductViewActionRelatedOutOfStockShowOutOfStockEnabled(): void
    {
        $productId = $this->productResource->getIdBySku('simple_product_1');
        $this->dispatch('catalog/product/view/id/' . $productId);
        $content = $this->getResponse()->getBody();
        $this->assertStringContainsString('<div class="block related"', $content);
        $this->assertStringContainsString('Simple Product 2 Name', $content);
        $this->assertStringContainsString('Simple Product 3 Name', $content);
    }

    /**
     * Covers Magento/TargetRule/view/frontend/catalog/product/list/upsell.html
     * Checks if up-sell products are displayed
     *
     * @magentoDbIsolation disabled
     *
     * @magentoDataFixture Magento/Catalog/controllers/_files/products.php
     * @magentoDataFixture Magento/TargetRule/_files/upsell.php
     */
    public function testProductViewActionUpsell()
    {
        $productId = $this->productResource->getIdBySku('simple_product_1');
        $this->dispatch('catalog/product/view/id/' . $productId);
        $content = $this->getResponse()->getBody();
        $this->assertStringContainsString('<div class="block upsell"', $content);
        $this->assertStringContainsString('Simple Product 2 Name', $content);
    }
}
