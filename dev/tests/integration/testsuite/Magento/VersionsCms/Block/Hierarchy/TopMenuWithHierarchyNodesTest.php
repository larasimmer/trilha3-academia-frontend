<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VersionsCms\Block\Hierarchy;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Helper\Xpath;
use Magento\Theme\Block\Html\Topmenu;
use PHPUnit\Framework\TestCase;

/**
 * Checks nodes appeared in top menu
 *
 * @magentoAppArea adminhtml
 */
class TopMenuWithHierarchyNodesTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var Menu */
    private $block;

    /** @var LayoutInterface */
    private $layout;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->layout = $this->objectManager->get(LayoutInterface::class);
        $this->block = $this->layout->createBlock(Topmenu::class);
    }

    /**
     * @magentoDataFixture Magento/VersionsCms/_files/hierarchy_menu_nodes.php
     * @return void
     */
    public function testHierarchyMenuItemsAddedToTopMenu(): void
    {
        $html = $this->block->getHtml();
        $xPathParent = "//li//a[contains(@href, 'page-1')]/span[contains(normalize-space(text()), 'Node 1')]";
        $xPathChild = "//li//ul//a[contains(@href, 'page-1/page-2')]/span[contains(normalize-space(text()), 'Node 2')]";
        $this->assertEquals(1, Xpath::getElementsCountForXpath($xPathParent, $html));
        $this->assertEquals(1, Xpath::getElementsCountForXpath($xPathChild, $html));
    }
}
