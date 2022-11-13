<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerSegment\Block\Adminhtml;

use Magento\Backend\Block\Menu;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Test for customer segment link in menu.
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
     * @magentoConfigFixture current_store customer/magento_customersegment/is_enabled 1
     *
     * @return void
     */
    public function testCustomerSegmentLink(): void
    {
        $block = $this->getMenuBlock();
        $item = $block->getMenuModel()->get('Magento_CustomerSegment::customer_customersegment');
        $this->assertNotNull($item);
        $this->assertFalse($item->isDisabled());
    }

    /**
     * @magentoConfigFixture current_store customer/magento_customersegment/is_enabled 0
     *
     * @return void
     */
    public function testCustomerSegmentLinkDisabled(): void
    {
        $block = $this->getMenuBlock();
        $item = $block->getMenuModel()->get('Magento_CustomerSegment::customer_customersegment');
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
