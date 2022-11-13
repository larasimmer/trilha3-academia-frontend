<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Block\Sku\Products;

use Magento\AdvancedCheckout\Helper\Data;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote\ItemFactory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Class checks info block displaying
 *
 * @see \Magento\AdvancedCheckout\Block\Sku\Products\Info
 *
 * @magentoAppArea frontend
 */
class InfoTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var Info */
    private $block;

    /** @var ItemFactory */
    private $quoteItemFactory;

    /** @var ProductRepositoryInterface */
    private $productRepository;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->block = $this->objectManager->get(LayoutInterface::class)->createBlock(Info::class);
        $this->quoteItemFactory = $this->objectManager->get(ItemFactory::class);
        $this->productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $this->productRepository->cleanCache();
    }

    /**
     * @dataProvider messagesProvider
     *
     * @magentoDataFixture Magento/Catalog/_files/second_product_simple.php
     *
     * @param string|null $code
     * @param string $expectedMessage
     * @param array $itemData
     * @return void
     */
    public function testGetMessage(?string $code, string $expectedMessage, array $itemData = []): void
    {
        $item = $this->prepareQuoteItem('simple2', $code, $itemData);
        $this->block->setItem($item);
        $this->assertEquals($expectedMessage, (string)$this->block->getMessage());
    }

    /**
     * @return array
     */
    public function messagesProvider(): array
    {
        return [
            'failed_sku' => [
                'code' => Data::ADD_ITEM_STATUS_FAILED_OUT_OF_STOCK,
                'expected_message' => '<span class="sku-out-of-stock" id="sku-stock-failed-">'
                    . 'Availability: Out of stock.</span>',
            ],
            'failed_qty_allowed' => [
                'code' => Data::ADD_ITEM_STATUS_FAILED_QTY_ALLOWED,
                'expected_message' => 'We don\'t have as many of these as you want.<br/>Only '
                    . '<span class="sku-failed-qty" id="sku-stock-failed-"></span> left in stock',
            ],
            'failed_qty_allowed_in_cart' => [
                'code' => Data::ADD_ITEM_STATUS_FAILED_QTY_ALLOWED_IN_CART,
                'expected_message' => 'You can\'t add this many to your cart.<br />',
            ],
            'failed_max_qty_allowed_in_cart' => [
                'code' => Data::ADD_ITEM_STATUS_FAILED_QTY_ALLOWED_IN_CART,
                'expected_message' => 'You can\'t add this many to your cart.<br />You can buy up to '
                    . '<span class="sku-failed-qty" id="sku-stock-failed-">1</span> of these per purchase.',
                'data' => [
                    'qty_max_allowed' => true,
                ],
            ],
            'failed_min_qty_allowed_in_cart' => [
                'code' => Data::ADD_ITEM_STATUS_FAILED_QTY_ALLOWED_IN_CART,
                'expected_message' => 'You can\'t add this many to your cart.<br />You must buy at least '
                    . '<span class="sku-failed-qty" id="sku-stock-failed-">1</span> of these per purchase.',
                'data' => [
                    'qty_min_allowed' => true,
                ],
            ],
            'some_another_failure_with_code' => [
                'code' => Data::ADD_ITEM_STATUS_FAILED_QTY_INVALID_NUMBER,
                'expected_message' => 'Please enter an actual number in the "Qty" field.',
            ],
            'some_another_failure_without_code' => [
                'code' => null,
                'expected_message' => 'test error',
                'data' => [
                    'error' => 'test error',
                ],
            ],
        ];
    }

    /**
     * @dataProvider linkProvider
     *
     * @magentoConfigFixture current_store catalog/productalert/allow_stock 1
     *
     * @magentoDataFixture Magento/Catalog/_files/second_product_simple.php
     *
     * @param string $code
     * @param string $expectedLink
     * @param string $expectedMessage
     * @return void
     */
    public function testGetLink(string $code, string $expectedLink, string $expectedMessage): void
    {
        $item = $this->prepareQuoteItem('simple2', $code);
        $this->block->setItem($item);
        $link = $this->block->getLink();
        $this->assertStringContainsString($expectedLink, $link);
        $this->assertStringContainsString($expectedMessage, strip_tags($link));
    }

    /**
     * @return array
     */
    public function linkProvider(): array
    {
        return [
            'failed_cofigure' => [
                'code' => Data::ADD_ITEM_STATUS_FAILED_CONFIGURE,
                'expected_link' => 'checkout/cart/configureFailed/id/6/sku/simple2',
                'expected_message' => 'Specify the product\'s options.',
            ],
            'product_out_of_stock' => [
                'code' => Data::ADD_ITEM_STATUS_FAILED_OUT_OF_STOCK,
                'expected_link' => 'productalert/add/stock/product_id/6/',
                'expected_message' => 'Alert me when this item is available.',
            ],
        ];
    }

    /**
     * Prepare quote item
     *
     * @param string $sku
     * @param string|null $code
     * @param array $itemData
     * @return Item
     */
    private function prepareQuoteItem(string $sku, ?string $code, array $itemData = []): Item
    {
        $product = $this->productRepository->get($sku);
        $quoteItem = $this->quoteItemFactory->create();
        $quoteItem->setProduct($product);
        $quoteItem->setCode($code);

        return $quoteItem->addData($itemData);
    }
}
