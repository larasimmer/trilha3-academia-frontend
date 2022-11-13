<?php
/***
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VersionsCms\Model\Hierarchy;

use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Model\Page;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Message\MessageInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\TestCase\AbstractBackendController;
use Magento\VersionsCms\Api\Data\HierarchyNodeInterface;
use Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node\Collection as NodeCollection;
use Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node\CollectionFactory as NodeCollectionFactory;

/**
 * Test for hierarchy nodes in backend
 */
class NodeTest extends AbstractBackendController
{
    /** @var Page */
    private $page;

    /** @var NodeCollectionFactory */
    private $nodeCollectionFactory;

    /** @var NodeCollection */
    private $nodeCollection;

    /** @var Json */
    private $serializer;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->page = $this->_objectManager->create(Page::class);
        $this->nodeCollectionFactory = $this->_objectManager->create(NodeCollectionFactory::class);
        $this->nodeCollection = $this->nodeCollectionFactory->create();
        $this->serializer = $this->_objectManager->create(Json::class);
        $this->storeManager = $this->_objectManager->get(StoreManagerInterface::class);
    }

    /**
     * Test of creating additional hierarchy nodes after duplicating page
     *
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/VersionsCms/_files/hierarcy_nodes_with_pages_on_different_websites.php
     */
    public function testAppendPageToNodes()
    {
        $this->page->load('page100');
        $this->assertEquals(5, $this->nodeCollectionFactory->create()->getSize());
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $requestData = $this->getRequestData();
        $this->getRequest()->setPostValue($requestData);
        $this->dispatch('backend/cms/page/save');
        $this->assertSessionMessages(
            $this->equalTo(['You saved the page.', 'You duplicated the page.']),
            MessageInterface::TYPE_SUCCESS
        );
        $this->assertEquals(7, $this->nodeCollectionFactory->create()->getSize());
        $this->checkDuplicatedNodes();
    }

    /**
     * Test of saving page assigned to multistores
     *
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/VersionsCms/_files/hierarchy_nodes_with_pages_on_different_websites_and_stores.php
     */
    public function testAppendPageToNodesMultiStores()
    {
        $this->page->load('page100');
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $requestData = $this->getMultiStoresRequestData();
        $this->getRequest()->setPostValue($requestData);
        $this->dispatch('backend/cms/page/save');
        $this->assertSessionMessages(
            $this->equalTo(['You saved the page.']),
            MessageInterface::TYPE_SUCCESS
        );
        $this->assertSessionMessages($this->isEmpty(), MessageInterface::TYPE_ERROR);
    }

    /**
     * Checking that records are created on the correct store
     *
     * @return void
     */
    private function checkDuplicatedNodes(): void
    {
        $requestUrl = 'main/' . $this->page->getIdentifier();
        $this->nodeCollection->clear();
        $scopeIds = $this->nodeCollection->addFieldToFilter(
            HierarchyNodeInterface::REQUEST_URL,
            $requestUrl
        )->getColumnValues(HierarchyNodeInterface::SCOPE_ID);
        $this->assertCount(2, $scopeIds);

        $duplicatedPage = $this->loadDuplicatedPage();
        $requestUrl = 'main/' . $duplicatedPage->getIdentifier();

        $newPageNodes = $this->nodeCollectionFactory->create()
            ->addFieldToFilter(HierarchyNodeInterface::REQUEST_URL, $requestUrl)
            ->toArray(
                [
                    HierarchyNodeInterface::SCOPE,
                    HierarchyNodeInterface::SCOPE_ID,
                    HierarchyNodeInterface::REQUEST_URL
                ]
            )['items'];

        $this->assertCount(2, $newPageNodes);

        $expectedNodes = [
            [
                HierarchyNodeInterface::SCOPE => 'default',
                HierarchyNodeInterface::SCOPE_ID => '0',
                HierarchyNodeInterface::REQUEST_URL => $requestUrl
            ],
            [
                HierarchyNodeInterface::SCOPE => 'store',
                HierarchyNodeInterface::SCOPE_ID => $this->storeManager->getStore('test')->getId(),
                HierarchyNodeInterface::REQUEST_URL => $requestUrl
            ],
        ];

        $this->assertSame($expectedNodes, $newPageNodes);
    }

    /**
     * Load duplicated page
     *
     * @return Page
     */
    private function loadDuplicatedPage(): Page
    {
        $cmsPageCollection = $this->page->getCollection()
            ->addFieldToFilter(PageInterface::IDENTIFIER, ['like' => $this->page->getIdentifier() . '-%'])
            ->load();

        return $cmsPageCollection->getLastItem();
    }

    /**
     * Preparing request data for dispatch page save controller and duplicate page
     *
     * @return array
     */
    private function getRequestData(): array
    {
        return [
            'page_id' => $this->page->getId(),
            'title' => $this->page->getTitle(),
            'page_layout' => '1column',
            'identifier' => $this->page->getIdentifier(),
            'is_active' => '1',
            'store_code' => 'admin',
            'store_id' => [Store::DEFAULT_STORE_ID, $this->storeManager->getStore('test')->getId()],
            'back' => 'duplicate',
            'nodes_data' => $this->getNodesData(),
        ];
    }

    /**
     * Return valid nodes data for request
     *
     * @return string
     */
    private function getNodesData(): string
    {
        /** @var NodeCollection $collection */
        $nodeCollection = $this->nodeCollection->load();
        $nodesData = [];
        foreach ($nodeCollection as $node) {
            $nodesData[$node->getId()] = [
                'node_id' => $node->getId(),
                'page_id' => $node->getPageId(),
                'parent_node_id' => $node->getParentNodeId(),
                'label' => $node->getLabel(),
                'sort_order' => (int)$node->getSortOrder(),
                'current_page' => $node->getPageId() ? true : false,
                'page_exists' => $node->getPageId() ? false : true,
            ];
            //add "use default" checkbox value which have not "node_id" in controller params
            if ((int)$node->getLevel() === 0) {
                $nodesData['_0'] = $nodesData[$node->getId()];
                $nodesData['_0']['node_id'] = '_0';
                unset($nodesData[$node->getId()]);
            }
        }
        return $this->serializer->serialize($nodesData);
    }

    /**
     * Preparing multi stores request data for dispatch page save controller
     *
     * @return array
     */
    private function getMultiStoresRequestData(): array
    {
        return [
            'page_id' => $this->page->getId(),
            'title' => $this->page->getTitle(),
            'page_layout' => '1column',
            'identifier' => $this->page->getIdentifier(),
            'is_active' => '1',
            'store_id' => array_keys($this->storeManager->getStores()),
            'nodes_data' => $this->getNodesData(),
        ];
    }
}
