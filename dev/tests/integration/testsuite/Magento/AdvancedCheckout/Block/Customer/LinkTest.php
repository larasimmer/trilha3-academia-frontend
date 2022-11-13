<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Block\Customer;

use Magento\Customer\Model\Session;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Checks Order by SKU link displaying in account dashboard
 *
 * @magentoAppArea frontend
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
class LinkTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var Page */
    private $page;

    /** @var Session */
    private $session;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->page = $this->objectManager->get(PageFactory::class)->create();
        $this->session = $this->objectManager->get(Session::class);
    }

    /**
     * @magentoConfigFixture current_store sales/product_sku/my_account_enable 1
     *
     * @return void
     */
    public function testOrderBySkuLinkEnabled(): void
    {
        $block = $this->getBlockByName('customer-account-navigation-checkout-sku-link');
        $this->assertNotFalse($block);
        $html = $block->toHtml();
        $this->assertStringContainsString('customer_order/sku/', $html);
        $this->assertEquals('Order by SKU', strip_tags($html));
    }

    /**
     * @magentoConfigFixture current_store sales/product_sku/my_account_enable 0
     *
     * @return void
     */
    public function testOrderBySkuLinkDisabled(): void
    {
        $block = $this->getBlockByName('customer-account-navigation-checkout-sku-link');
        $this->assertNotFalse($block);
        $this->assertEmpty($block->toHtml());
    }

    /**
     * @magentoConfigFixture current_store sales/product_sku/my_account_enable 2
     * @magentoConfigFixture current_store sales/product_sku/allowed_groups 101
     *
     * @magentoDataFixture Magento/Customer/_files/customer.php
     *
     * @return void
     */
    public function testOrderBySkuLinkDisabledForSpecificGroup(): void
    {
        $this->session->loginById(1);
        $block = $this->getBlockByName('customer-account-navigation-checkout-sku-link');
        $this->assertNotFalse($block);
        $this->assertEmpty($block->toHtml());
    }

    /**
     * Retrieve block from layout
     *
     * @param string $name
     * @return bool|BlockInterface
     */
    private function getBlockByName(string $name)
    {
        $this->preparePage();

        return $this->page->getLayout()->getBlock($name);
    }

    /**
     * Prepare page before render
     *
     * @return void
     */
    private function preparePage(): void
    {
        $this->page->addHandle([
            'default',
            'customer_account',
        ]);
        $this->page->getLayout()->generateXml();
    }
}
