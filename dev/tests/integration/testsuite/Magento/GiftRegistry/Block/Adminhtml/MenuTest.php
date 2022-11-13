<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Block\Adminhtml;

use Magento\Backend\Block\Menu;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Test for gift registry link in menu.
 *
 * @magentoAppArea adminhtml
 */
class MenuTest extends TestCase
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
    public function testGiftRegistryLink(): void
    {
        $block = $this->getMenuBlock();
        $item = $block->getMenuModel()->get('Magento_GiftRegistry::customer_magento_giftregistry');
        $this->assertNotNull($item);
        $this->assertFalse($item->isDisabled());
    }

    /**
     * @magentoConfigFixture current_store magento_giftregistry/general/enabled 0
     *
     * @return void
     */
    public function testGiftRegistryLinkDisabled(): void
    {
        $block = $this->getMenuBlock();
        $item = $block->getMenuModel()->get('Magento_GiftRegistry::customer_magento_giftregistry');
        $this->assertNotNull($item);
        $this->assertTrue($item->isDisabled());
    }

    /**
     * Get menu block.
     *
     * @return Menu
     */
    private function getMenuBlock(): Menu
    {
        $this->page->addHandle(['default']);
        $this->page->getLayout()->generateXml();
        $block = $this->page->getLayout()->getBlock('menu');
        $this->assertNotFalse($block);

        return $block;
    }
}
