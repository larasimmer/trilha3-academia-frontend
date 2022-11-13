<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VersionsCms\Block\Widget\Node;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Helper\Xpath;
use PHPUnit\Framework\TestCase;

/**
 * Test a Node showed on a simple product page
 *
 * @magentoAppArea frontend
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
class NodeOnSimpleProductPageTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var PageFactory */
    private $pageFactory;

    /** @var Page */
    private $resultPage;

    /** @var ProductRepositoryInterface */
    private $productRepository;

    /** @var Registry */
    private $registry;

    /** @var RequestInterface */
    private $request;

    /**
     * @inheritdoc
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $objectManager = Bootstrap::getObjectManager();
        /** @var Manager $moduleManager */
        $moduleManager = $objectManager->get(Manager::class);
        if (!$moduleManager->isEnabled('Magento_Catalog')) {
            self::markTestSkipped('Magento_Catalog module disabled.');
        }
    }

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->pageFactory = $this->objectManager->get(PageFactory::class);
        $this->registry = $this->objectManager->get(Registry::class);
        $this->productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $this->productRepository->cleanCache();
        $this->request = $this->objectManager->get(RequestInterface::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->clearData();
    }

    /**
     * @magentoCache layout disabled
     * @magentoDataFixture Magento/Catalog/_files/second_product_simple.php
     * @magentoDataFixture Magento/VersionsCms/_files/widget_with_hierarchy_node.php
     * @return void
     */
    public function testWidgetWithNodeShowedOnProductSimplePage(): void
    {
        $this->setActiveProduct('simple2');
        $this->preparePage();
        $output = $this->resultPage->getLayout()->renderElement('content');
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(
                $this->getXpathForWidgetNode('/home', 'Title text', 'Test Node Anchor text'),
                $output
            )
        );
    }

    /**
     * @magentoCache layout disabled
     * @magentoDataFixture Magento/Catalog/_files/second_product_simple.php
     * @magentoDataFixture Magento/VersionsCms/_files/widget_with_store_specific_hierarchy_node.php
     * @return void
     */
    public function testWidgetWithStoreSpecificNodeShowedOnProductSimplePage(): void
    {
        $this->setActiveProduct('simple2');
        $this->preparePage();
        $output = $this->resultPage->getLayout()->renderElement('content');
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(
                $this->getXpathForWidgetNode('/home', 'Title', 'Default store node text'),
                $output
            )
        );
    }

    /**
     * Remove product from registry and request
     *
     * @return void
     */
    private function clearData(): void
    {
        $this->request->setParams([]);
        $this->registry->unregister('product');
        $this->registry->unregister('current_product');
    }

    /**
     * Set a product to registry and request
     *
     * @param string $productSku
     * @return void
     */
    private function setActiveProduct(string $productSku): void
    {
        $product = $this->productRepository->get($productSku);
        $this->clearData();
        $this->request->setParams(['id' => $product->getId()]);
        $this->registry->register('product', $product);
        $this->registry->register('current_product', $product);
    }

    /**
     * Create and prepare a page
     *
     * @return void
     */
    private function preparePage(): void
    {
        $this->resultPage = $this->pageFactory->create();
        $this->resultPage->addHandle(['default', 'catalog_product_view', 'catalog_product_view_type_simple']);
        $this->resultPage->getLayout()->generateXml();
    }

    /**
     * Prepare Xpath
     *
     * @param string $href
     * @param string $title
     * @param string $text
     * @return string
     */
    private function getXpathForWidgetNode(string $href, string $title, string $text): string
    {
        return sprintf("//div[contains(@class, 'widget') and contains(@class, 'block-cms-hierarchy-link')]/"
            . "a[contains(@href, '%s') and contains(@title, '%s')]/span[contains(text(), '%s')]", $href, $title, $text);
    }
}
