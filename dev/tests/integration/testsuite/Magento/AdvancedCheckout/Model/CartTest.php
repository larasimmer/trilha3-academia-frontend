<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Model;

use Magento\AdvancedCheckout\Helper\Data;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Store\ExecuteInStoreContext;
use PHPUnit\Framework\TestCase;

/**
 * Class checks checkout processing behaviour
 *
 * @see \Magento\AdvancedCheckout\Model\Cart
 *
 * @magentoAppArea frontend
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class CartTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var Cart */
    private $cart;

    /** @var ExecuteInStoreContext */
    private $executeInStoreContext;

    /** @var ProductRepositoryInterface */
    private $productRepository;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->cart = $this->objectManager->get(Cart::class);
        $this->cart->setContext(Cart::CONTEXT_FRONTEND);
        $this->executeInStoreContext = $this->objectManager->get(ExecuteInStoreContext::class);
        $this->productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $this->productRepository->cleanCache();
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/product_simple_xss.php
     *
     * @return void
     */
    public function testPrepareProductsBySku(): void
    {
        $this->cart->prepareAddProductsBySku([['sku' => 'product-with-xss', 'qty' => 1000]]);
        $failedItems = $this->cart->getFailedItems();
        $this->assertNotEmpty($failedItems);
        $failedItemInfo = reset($failedItems);
        $this->assertEquals(Data::ADD_ITEM_STATUS_FAILED_QTY_ALLOWED, $failedItemInfo['code']);
        $failedItem = $failedItemInfo['item'];
        $this->assertEquals(__('The requested qty is not available'), $failedItem['error']);
    }

    /**
     * @return void
     */
    public function testCheckItemNonExistingSku(): void
    {
        $result = $this->cart->checkItem('non-existing-sku', 150);
        $this->assertEquals(Data::ADD_ITEM_STATUS_FAILED_SKU, $result['code']);
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/simple_product_disabled.php
     *
     * @return void
     */
    public function testCheckItemDisabledProduct(): void
    {
        $result = $this->cart->checkItem('product_disabled', 150);
        $this->assertEquals(Data::ADD_ITEM_STATUS_FAILED_SKU, $result['code']);
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/product_simple_out_of_stock.php
     *
     * @return void
     */
    public function testCheckItemOutOfStockProduct(): void
    {
        $productSku = 'simple-out-of-stock';
        $itemsArray = [
            'sku' => $productSku,
            'qty' => 150,
            'code' => Data::ADD_ITEM_STATUS_SUCCESS,
        ];
        $result = $this->cart->checkItems([$itemsArray], 150);
        $this->assertArrayHasKey($productSku, $result);
        $this->assertEquals(Data::ADD_ITEM_STATUS_FAILED_OUT_OF_STOCK, $result[$productSku]['code']);
    }

    /**
     * @magentoConfigFixture current_store cataloginventory/item_options/max_sale_qty 1
     *
     * @magentoDataFixture Magento/Catalog/_files/product_simple_xss.php
     *
     * @return void
     */
    public function testCheckItemLimitExceeded(): void
    {
        $result = $this->cart->checkItem('product-with-xss', 2);
        $this->assertEquals(Data::ADD_ITEM_STATUS_FAILED_QTY_ALLOWED_IN_CART, $result['code']);
    }

    /**
     * @magentoDataFixture Magento/ConfigurableProduct/_files/configurable_product_with_one_simple.php
     *
     * @return void
     */
    public function testCheckItemConfigurableProduct(): void
    {
        $result = $this->cart->checkItem('configurable', 1);
        $this->assertEquals(Data::ADD_ITEM_STATUS_FAILED_CONFIGURE, $result['code']);
    }

    /**
     * @magentoDataFixture Magento/Bundle/_files/bundle_product_radio_options.php
     *
     * @return void
     */
    public function testCheckItemBundleProduct(): void
    {
        $result = $this->cart->checkItem('bundle-product-radio-options', 1);
        $this->assertEquals(Data::ADD_ITEM_STATUS_FAILED_CONFIGURE, $result['code']);
    }

    /**
     * @magentoDbIsolation disabled
     *
     * @magentoDataFixture Magento/Store/_files/second_website_with_two_stores.php
     * @magentoDataFixture Magento/Catalog/_files/product_simple_xss.php
     *
     * @return void
     */
    public function testCheckItemUnAssignedWebsite(): void
    {
        $result = $this->executeInStoreContext->execute(
            'fixture_third_store',
            [$this->cart, 'checkItem'],
            'product-with-xss',
            1
        );
        $this->assertEquals(Data::ADD_ITEM_STATUS_FAILED_SKU, $result['code']);
    }

    /**
     * @magentoConfigFixture current_store cataloginventory/item_options/min_sale_qty 10
     *
     * @magentoDataFixture Magento/Catalog/_files/product_simple_xss.php
     *
     * @return void
     */
    public function testGetQtyStatusMinAllowed(): void
    {
        $product = $this->productRepository->get('product-with-xss');
        $result = $this->cart->getQtyStatus($product, 1);
        $this->assertNotTrue($result);
        $this->assertEquals(Data::ADD_ITEM_STATUS_FAILED_QTY_ALLOWED_IN_CART, $result['status']);
        $this->assertEquals((string)__('The fewest you may purchase is %1.', 10), (string)$result['error']);
    }

    /**
     * @magentoConfigFixture current_store cataloginventory/item_options/enable_qty_increments 1
     * @magentoConfigFixture current_store cataloginventory/item_options/qty_increments 3
     *
     * @magentoDataFixture Magento/Catalog/_files/product_simple_xss.php
     *
     * @return void
     */
    public function testGetQtyStatusQtyIncrement(): void
    {
        $product = $this->productRepository->get('product-with-xss');
        $result = $this->cart->getQtyStatus($product, 1);
        $this->assertNotTrue($result);
        $this->assertEquals(Data::ADD_ITEM_STATUS_FAILED_QTY_INCREMENTS, $result['status']);
        $this->assertEquals(
            (string)__('You can buy this product only in quantities of %1 at a time.', 3),
            (string)$result['error']
        );
    }

    /**
     * Test for @see \Magento\AdvancedCheckout\Model\Cart::prepareAddProductsBySku with Latin and Cyrillic symbols.
     *
     * @magentoDataFixture Magento/Catalog/_files/product_virtual.php
     * @magentoDataFixture Magento/Catalog/_files/simple_product_cyrillic_symbols.php
     * @dataProvider differentWritingSystemSearchRequestDataProvider
     * @param array $request
     * @return void
     */
    public function testPrepareProductsToAddBySku(array $request): void
    {
        $sku = $request[0]['sku'];
        $qty = $request[0]['qty'];

        $this->cart->prepareAddProductsBySku($request);
        $data = $this->cart->getAffectedItems();

        $this->assertEquals('success', $data[$sku]['code']);
        $this->assertEquals($qty, $data[$sku]['orig_qty']);
    }

    /**
     * Return search requests in different writing systems.
     *
     * @return array
     */
    public function differentWritingSystemSearchRequestDataProvider(): array
    {
        return [
            'Lowercase Latin search request' => [[['sku' => 'virtual-product', 'qty' => '1']]],
            'Uppercase Latin search request' => [[['sku' => 'Virtual-product', 'qty' => '2']]],
            'Lowercase Cyrillic search request' => [[['sku' => 'Продукт', 'qty' => '3']]],
            'Uppercase Cyrillic search request' => [[['sku' => 'продукт', 'qty' => '4']]],
        ];
    }

    /**
     * Test check that items with non existing sku executed correctly
     *
     * @magentoDataFixture Magento/Catalog/_files/product_with_category.php
     *
     * @return void
     */
    public function testCheckItemsWithNonExistingSku(): void
    {
        $itemsArray = [
            [
                'sku' => 'simple_not_exist',
                'qty' => 1,
                'code' => Data::ADD_ITEM_STATUS_SUCCESS,
            ],
            [
                'sku' => 'in-stock-product',
                'qty' => 1,
                'code' => Data::ADD_ITEM_STATUS_SUCCESS,
            ],
        ];
        $result = $this->cart->checkItems($itemsArray);
        $this->assertArrayHasKey('simple_not_exist', $result);
        $this->assertEquals(Data::ADD_ITEM_STATUS_FAILED_SKU, $result['simple_not_exist']['code']);
        $this->assertArrayHasKey('in-stock-product', $result);
        $this->assertEquals(Data::ADD_ITEM_STATUS_SUCCESS, $result['in-stock-product']['code']);
    }
}
