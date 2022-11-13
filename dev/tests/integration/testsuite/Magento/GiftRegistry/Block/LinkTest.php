<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Block;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Test for displaying gift registry link in customer account dashboard.
 *
 * @magentoAppArea frontend
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
     * @magentoConfigFixture current_store magento_giftregistry/general/enabled 1
     *
     * @return void
     */
    public function testGiftRegistryLinkEnabled(): void
    {
        $this->preparePage();
        $block = $this->page->getLayout()->getBlock('customer-account-navigation-giftregistry-link');
        $this->assertNotFalse($block);
        $html = $block->toHtml();
        $this->assertStringContainsString('/giftregistry/', $html);
        $this->assertEquals((string)__('Gift Registry'), strip_tags($html));
    }

    /**
     * @magentoConfigFixture current_store magento_giftregistry/general/enabled 0
     *
     * @return void
     */
    public function testGiftRegistryLinkDisabled(): void
    {
        $this->preparePage();
        $block = $this->page->getLayout()->getBlock('customer-account-navigation-giftregistry-link');
        $this->assertNotFalse($block);
        $this->assertEmpty($block->toHtml());
    }

    /**
     * Prepare page before render.
     *
     * @return void
     */
    private function preparePage(): void
    {
        $this->page->addHandle(['default', 'customer_account']);
        $this->page->getLayout()->generateXml();
    }
}
