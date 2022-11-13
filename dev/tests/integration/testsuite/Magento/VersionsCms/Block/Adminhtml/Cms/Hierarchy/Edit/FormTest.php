<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VersionsCms\Block\Adminhtml\Cms\Hierarchy\Edit;

use Magento\Catalog\Block\Product\AbstractProduct;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\LayoutInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\VersionsCms\Block\Adminhtml\Cms\Hierarchy\Edit\Form\Grid;
use Magento\VersionsCms\Helper\Hierarchy;
use Magento\VersionsCms\Model\Hierarchy\Node;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 */
class FormTest extends TestCase
{
    /** @var LayoutInterface */
    protected $_layout = null;

    /** @var Form */
    protected $_block = null;

    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var Json
     */
    protected $serializer;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->_layout = Bootstrap::getObjectManager()->get(
            LayoutInterface::class
        );
        $this->_block = $this->_layout->createBlock(
            Form::class
        );
        $this->coreRegistry = Bootstrap::getObjectManager()->get(Registry::class);
        $this->serializer = Bootstrap::getObjectManager()->create(Json::class);
    }

    /**
     * Test Test for method getGridJsObject
     *
     * @return void
     */
    public function testGetGridJsObject(): void
    {
        $parentName = 'parent';
        $this->_layout->createBlock(AbstractProduct::class, $parentName);
        $this->_layout->setChild($parentName, $this->_block->getNameInLayout(), '');

        $pageGrid = $this->_layout->addBlock(
            Grid::class,
            'cms_page_grid',
            $parentName
        );
        $this->assertEquals($pageGrid->getJsObjectName(), $this->_block->getGridJsObject());
    }

    /**
     * Test for prepare form
     *
     * @param int $isMetadataEnabled
     * @param bool $result
     * @return void
     *
     * @dataProvider prepareFormDataProvider
     */
    public function testPrepareForm($isMetadataEnabled, $result): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $cmsHierarchyMock = $this->getMockBuilder(Hierarchy::class)
            ->setMethods(['isMetadataEnabled'])
            ->disableOriginalConstructor()
            ->getMock();
        $cmsHierarchyMock->expects($this->any())
            ->method('isMetadataEnabled')
            ->willReturn($isMetadataEnabled);
        $block = $objectManager->create(
            Form::class,
            ['cmsHierarchy' =>$cmsHierarchyMock]
        );
        $prepareFormMethod = new \ReflectionMethod(
            Form::class,
            '_prepareForm'
        );
        $prepareFormMethod->setAccessible(true);
        $prepareFormMethod->invoke($block);
        $form = $block->getForm();
        $this->assertEquals($result, ($form->getElement('top_menu_fieldset') === null));
        $this->assertEquals('validate-no-html-tags', $form->getElement('node_label')->getClass());
    }

    /**
     * Data provider for testPrepareForm
     *
     * @return array
     */
    public function prepareFormDataProvider()
    {
        return [
            [1, false],
            [0, false]
        ];
    }

    /**
     * Test for method getNodesJson
     *
     * @param string $scope
     * @param int $scopeId
     * @param int $nodesCount
     * @return void
     *
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/VersionsCms/_files/hierarchy_nodes_with_pages_on_different_websites_and_stores.php
     * @dataProvider getNodesDataProvider
     */
    public function testGetNodesJson(string $scope, int $scopeId, int $nodesCount): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $nodeModel = $objectManager->create(
            Node::class,
            ['data' => ['scope' => $scope, 'scope_id' => $scopeId]]
        );
        $this->coreRegistry->unregister('current_hierarchy_node');
        $this->coreRegistry->register('current_hierarchy_node', $nodeModel);

        $form = $objectManager->create(Form::class);
        $nodes = $form->getNodesJson();

        $this->assertEquals($nodesCount, count($this->serializer->unserialize($nodes)));
    }

    /**
     * Data provider for testGetNodesJson
     *
     * @return array
     */
    public function getNodesDataProvider(): array
    {
        return [
            [
                'scope' => Node::NODE_SCOPE_DEFAULT,
                'scopeId' => Node::NODE_SCOPE_DEFAULT_ID,
                'nodesCount' => 2,
            ],
            [
                'scope' => Node::NODE_SCOPE_STORE,
                'scopeId' => 1,
                'nodesCount' => 0,
            ],
        ];
    }
}
