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
 * @magentoAppArea adminhtml
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class AdminCartTest extends TestCase
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
        $this->cart = $this->objectManager->create(CartFactory::class)->create();
        $this->cart->setContext(Cart::CONTEXT_ADMIN_CHECKOUT);
        $this->executeInStoreContext = $this->objectManager->get(ExecuteInStoreContext::class);
        $this->productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $this->productRepository->cleanCache();
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/simple_product_disabled.php
     *
     * @return void
     */
    public function testCheckItemDisabledProduct(): void
    {
        $result = $this->cart->checkItem('product_disabled', 150);
        $this->assertEquals(Data::ADD_ITEM_STATUS_FAILED_DISABLED, $result['code']);
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
        $this->assertEquals(Data::ADD_ITEM_STATUS_FAILED_WEBSITE, $result['code']);
    }
}
