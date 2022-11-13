<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Block\Adminhtml\Manage;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\LayoutInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Quote\Model\GetQuoteByReservedOrderId;
use PHPUnit\Framework\TestCase;

/**
 * Checks Items block appearance
 *
 * @see \Magento\AdvancedCheckout\Block\Adminhtml\Manage\Items
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation disabled
 * @magentoAppIsolation enabled
 */
class ItemsTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var Items */
    private $block;

    /** @var Registry */
    private $registry;

    /** @var CartRepositoryInterface */
    private $quoteRepository;

    /** @var GetQuoteByReservedOrderId */
    private $getQuoteByReservedOrderId;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->registry = $this->objectManager->get(Registry::class);
        $this->quoteRepository = $this->objectManager->get(CartRepositoryInterface::class);
        $this->block = $this->objectManager->get(LayoutInterface::class)->createBlock(Items::class);
        $this->getQuoteByReservedOrderId = $this->objectManager->get(GetQuoteByReservedOrderId::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        $this->registry->unregister('checkout_current_quote');

        parent::tearDown();
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/quote_with_customer.php
     *
     * @return void
     */
    public function testGetSubtotal(): void
    {
        $quote = $this->quoteRepository->getForCustomer(1);
        $this->registry->register('checkout_current_quote', $quote);
        $this->assertEquals('10.0000', $this->block->getSubtotal());
    }

    /**
     * @magentoDataFixture Magento/Checkout/_files/active_quote_with_downloadable_product.php
     *
     * @return void
     */
    public function testGetSubTotalWithVirtualProduct(): void
    {
        $quote = $this->getQuoteByReservedOrderId->execute('test_order_1');
        $this->registry->register('checkout_current_quote', $quote);
        $this->assertEquals('10.0000', $this->block->getSubtotal());
    }

    /**
     * @magentoConfigFixture current_store tax/cart_display/subtotal 2
     *
     * @magentoDataFixture Magento/Tax/_files/tax_rule_region_1_al.php
     * @magentoDataFixture Magento/Checkout/_files/quote_with_taxable_product_and_customer.php
     *
     * @return void
     */
    public function testSubTotalWithTaxEnabled(): void
    {
        $quote = $this->getQuoteByReservedOrderId->execute('test_order_with_taxable_product');
        $this->registry->register('checkout_current_quote', $quote);
        $this->assertEquals('10.75', $this->block->getSubtotal());
    }

    /**
     * @magentoConfigFixture current_store tax/cart_display/subtotal 1
     *
     * @magentoDataFixture Magento/Tax/_files/tax_rule_region_1_al.php
     * @magentoDataFixture Magento/Checkout/_files/quote_with_taxable_product_and_customer.php
     *
     * @return void
     */
    public function testSubTotalWithTaxDisabled(): void
    {
        $quote = $this->getQuoteByReservedOrderId->execute('test_order_with_taxable_product');
        $this->registry->register('checkout_current_quote', $quote);
        $this->assertEquals('10.0000', $this->block->getSubtotal());
    }

    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_simple_product_saved.php
     *
     * @return void
     */
    public function testGetConfigureButtonHtmlSimpleProduct(): void
    {
        $item = $this->getQuoteItem('test_order_with_simple_product_without_address');
        $result = $this->block->getConfigureButtonHtml($item);
        $this->assertStringContainsString('scalable disabled', $result);
        $this->assertStringContainsString('disabled="disabled"', $result);
    }

    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_bundle_product.php
     *
     * @return void
     */
    public function testGetConfigureButtonHtmlCompositeProduct(): void
    {
        $item = $this->getQuoteItem('test_cart_with_bundle');
        $result = $this->block->getConfigureButtonHtml($item);
        $this->assertStringContainsString(
            sprintf('onclick="checkoutObj.showQuoteItemConfiguration(%s)"', $item->getId()),
            $result
        );
    }

    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_not_visible_individually_product.php
     *
     * @return void
     */
    public function testMoveToWishlistIsNotAllowed(): void
    {
        $item = $this->getQuoteItem('test_order_with_not_visible_product');
        $this->assertFalse($this->block->isMoveToWishlistAllowed($item));
    }

    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_simple_product_saved.php
     *
     * @return void
     */
    public function testMoveToWishlistIsAllowed(): void
    {
        $item = $this->getQuoteItem('test_order_with_simple_product_without_address');
        $this->assertTrue($this->block->isMoveToWishlistAllowed($item));
    }

    /**
     * Get first quote item
     *
     * @param string $reservedOrderId
     * @return CartItemInterface
     */
    private function getQuoteItem(string $reservedOrderId): CartItemInterface
    {
        $quote = $this->getQuoteByReservedOrderId->execute($reservedOrderId);
        $items = $quote->getItems();

        return reset($items);
    }
}
