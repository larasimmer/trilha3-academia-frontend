<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerBalance\Block\Account;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Checks store credit link displaying in account dashboard.
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

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->page = $this->objectManager->get(PageFactory::class)->create();
    }

    /**
     * @return void
     */
    public function testStoreCreditLink(): void
    {
        $this->preparePage();
        $block = $this->page->getLayout()->getBlock('customer-account-navigation-customer-balance-link');
        $this->assertNotFalse($block);
        $html = $block->toHtml();
        $this->assertStringContainsString('/storecredit/info/', $html);
        $this->assertEquals((string)__('Store Credit'), strip_tags($html));
    }

    /**
     * @magentoConfigFixture current_store customer/magento_customerbalance/is_enabled 0
     *
     * @return void
     */
    public function testStoreCreditLinkDisabled(): void
    {
        $this->preparePage();
        $block = $this->page->getLayout()->getBlock('customer-account-navigation-customer-balance-link');
        $this->assertFalse($block);
    }

    /**
     * Prepare page before render.
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
