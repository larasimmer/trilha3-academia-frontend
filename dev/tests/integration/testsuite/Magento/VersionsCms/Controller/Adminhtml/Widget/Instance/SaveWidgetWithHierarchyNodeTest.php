<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VersionsCms\Controller\Adminhtml\Widget\Instance;

use Magento\Catalog\Model\Product\Type;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Message\MessageInterface;
use Magento\Store\Model\Store;
use Magento\TestFramework\TestCase\AbstractBackendController;
use Magento\Widget\Model\ResourceModel\Widget\Instance\CollectionFactory as WidgetCollectionFactory;
use Magento\Theme\Model\ResourceModel\Theme\CollectionFactory as ThemeCollectionFactory;
use Magento\Theme\Model\ResourceModel\Theme\Collection as ThemeCollection;
use Magento\VersionsCms\Api\Data\HierarchyNodeInterfaceFactory;
use Magento\VersionsCms\Model\Hierarchy\Node;
use Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node as NodeResource;
use Magento\Widget\Model\ResourceModel\Widget\Instance as InstanceResource;

/**
 * Test for save widget with hierarchy node
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class SaveWidgetWithHierarchyNodeTest extends AbstractBackendController
{
    /** @var InstanceResource */
    private $widgetResource;

    /** @var WidgetCollectionFactory */
    private $widgetCollectionFactory;

    /** @var HierarchyNodeInterfaceFactory */
    private $nodeFactory;

    /** @var NodeResource */
    private $nodeResource;

    /** @var ThemeCollectionFactory */
    private $themeListFactory;

    /** @var ThemeCollection */
    private $themeCollection;

    /** @var */
    private $itemsToDelete = [];

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->widgetResource = $this->_objectManager->get(InstanceResource::class);
        $this->widgetCollectionFactory = $this->_objectManager->get(WidgetCollectionFactory::class);
        $this->nodeFactory = $this->_objectManager->get(HierarchyNodeInterfaceFactory::class);
        $this->nodeResource = $this->_objectManager->get(NodeResource::class);
        $this->themeListFactory = $this->_objectManager->get(ThemeCollectionFactory::class);
        $this->themeCollection = $this->themeListFactory->create();
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $widgetCollection = $this->widgetCollectionFactory->create();
        $widgets = $widgetCollection->addFieldToFilter('title', ['in' => $this->itemsToDelete]);
        foreach ($widgets as $widget) {
            $this->widgetResource->delete($widget);
        }
    }

    /**
     * @magentoDataFixture Magento/VersionsCms/_files/hierarchy_node_with_default_store.php
     * @return void
     */
    public function testSaveWidget(): void
    {
        $this->itemsToDelete[] = 'Test Widget One';
        $node = $this->nodeFactory->create();
        $this->nodeResource->load($node, 'simple_node', Node::IDENTIFIER);
        $this->getRequest()->setPostValue([
            'title' => 'Test Widget One',
            'store_ids' => [Store::DEFAULT_STORE_ID],
            'sort_order' => 1,
            'code' => 'cms_hierarchy_node',
            'theme_id' => $this->themeCollection->getThemeByFullPath('frontend/Magento/luma')->getThemeId(),
            'widget_instance' => [
                [
                    'page_group' => 'simple_products',
                    'simple_products' => [
                        'page_id' => '0',
                        'layout_handle' => 'catalog_product_view_type_simple',
                        'for' => 'all',
                        'block' => 'content',
                        'template' => 'hierarchy/widget/link/link_block.phtml',
                        'product_type_id' => Type::TYPE_SIMPLE,
                    ],
                ],
            ],
            'parameters' => ['node_id_0' => $node->getId()],
        ]);
        $this->getRequest()->setMethod(Http::METHOD_POST);
        $this->dispatch('backend/admin/widget_instance/save');
        $this->assertSessionMessages(
            $this->containsEqual((string)__('The widget instance has been saved.')),
            MessageInterface::TYPE_SUCCESS
        );
        $this->assertRedirect($this->stringContains('admin/widget_instance/index'));
    }
}
