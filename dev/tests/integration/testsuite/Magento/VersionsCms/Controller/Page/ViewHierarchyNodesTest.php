<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VersionsCms\Controller\Page;

use Laminas\Http\Response;
use Magento\Cms\Api\GetPageByIdentifierInterface;
use Magento\TestFramework\Helper\Xpath;
use Magento\TestFramework\TestCase\AbstractController;

/**
 * Cms page hierarchy nodes test
 *
 * @magentoAppArea frontend
 */
class ViewHierarchyNodesTest extends AbstractController
{
    /** @var GetPageByIdentifierInterface */
    private $getPageByIdentifier;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->getPageByIdentifier = $this->_objectManager->get(GetPageByIdentifierInterface::class);
    }

    /**
     * @magentoDataFixture Magento/VersionsCms/_files/hierarchy_menu_nodes.php
     * @return void
     */
    public function testNodesAreDisplayedOnPage(): void
    {
        $page = $this->getPageByIdentifier->execute('page-1', 0);
        $this->dispatch('/cms/page/view/page_id/' . (int)$page->getId());
        $this->assertEquals(Response::STATUS_CODE_200, $this->getResponse()->getStatusCode());
        $bodyHtml = $this->getResponse()->getBody();
        $parentNodeTreeXpath = "//ul[contains(@class, 'cms-menu')]/li/strong[normalize-space(text())='Node 1']";
        $this->assertEquals(1, Xpath::getElementsCountForXpath($parentNodeTreeXpath, $bodyHtml));
        $childNodeXpath = "//ul[contains(@class, 'cms-menu')]//ul//a[contains(@href, 'page-1/page-2')]/"
        . "span[normalize-space(text())='Node 2']";
        $this->assertEquals(1, Xpath::getElementsCountForXpath($childNodeXpath, $bodyHtml));
    }
}
