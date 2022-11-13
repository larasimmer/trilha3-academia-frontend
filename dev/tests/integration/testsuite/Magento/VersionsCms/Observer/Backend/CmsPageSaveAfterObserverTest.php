<?php
/***
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VersionsCms\Observer\Backend;

use Magento\Cms\Api\Data\PageInterfaceFactory;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Message\MessageInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\TestFramework\TestCase\AbstractBackendController;
use Magento\VersionsCms\Api\Data\HierarchyNodeInterface;
use Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node\CollectionFactory as NodeCollectionFactory;

/**
 * 'Create and delete nodes' Observer integration tests.
 *
 * @magentoAppArea adminhtml
 */
class CmsPageSaveAfterObserverTest extends AbstractBackendController
{
    /**
     * @inheritDoc
     */
    protected $uri = 'backend/cms/page/save';

    /**
     * @inheritDoc
     */
    protected $httpMethod = HttpRequest::METHOD_POST;

    /**
     * @var PageInterfaceFactory
     */
    private $pageFactory;

    /**
     * @var NodeCollectionFactory
     */
    private $nodeCollectionFactory;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->pageFactory = $this->_objectManager->get(PageInterfaceFactory::class);
        $this->nodeCollectionFactory = $this->_objectManager->get(NodeCollectionFactory::class);
        $this->serializer = $this->_objectManager->get(SerializerInterface::class);
    }

    /**
     * Test CMS Page is saved successfully two times with the same Nodes.
     *
     * @return void
     * @magentoDataFixture Magento/VersionsCms/_files/hierarchy_nodes_with_pages_on_different_scopes.php
     */
    public function testSaveCmsPageTwoTimesWithSameNodes(): void
    {
        $parentPage = $this->pageFactory->create()->load('page100');
        $childPage = $this->pageFactory->create()->load('page_design_blank');
        /** @var HierarchyNodeInterface[] $parentNodes */
        $parentNodes = $this->getNodesByPageId((int)$parentPage->getId());
        $postData = $childPage->getData();
        $postData['nodes_data'] = $this->getNodesData($parentNodes);
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue($postData);

        $this->dispatch($this->uri);
        $this->assertSessionMessages(
            $this->equalTo([(string)__('You saved the page.')]),
            MessageInterface::TYPE_SUCCESS
        );
        $newNodes = $this->getNodesByPageId((int)$childPage->getId());
        $this->assertCount(3, $newNodes);

        $this->dispatch($this->uri);
        $this->assertSessionMessages($this->isEmpty(), MessageInterface::TYPE_ERROR);
    }

    /**
     * Retrieve Hierarchy Nodes by Page ID.
     *
     * @param int $pageId
     * @return array
     */
    private function getNodesByPageId(int $pageId): array
    {
        $nodeCollection = $this->nodeCollectionFactory->create();
        $nodeCollection->addFieldToFilter(HierarchyNodeInterface::PAGE_ID, $pageId);

        return $nodeCollection->getItems();
    }

    /**
     * Retrieve valid request data for provided nodes.
     *
     * @param HierarchyNodeInterface[] $nodes
     * @return string
     */
    private function getNodesData(array $nodes): string
    {
        $nodesData = [];
        foreach ($nodes as $node) {
            $nodesData[$node->getId()] = [
                HierarchyNodeInterface::NODE_ID => $node->getId(),
                HierarchyNodeInterface::PAGE_ID => $node->getPageId(),
                HierarchyNodeInterface::PARENT_NODE_ID => $node->getParentNodeId(),
                HierarchyNodeInterface::LABEL => $node->getLabel(),
                HierarchyNodeInterface::SORT_ORDER => (int)$node->getSortOrder(),
                'current_page' => true,
                'page_exists' => true,
            ];
        }

        return $this->serializer->serialize($nodesData);
    }
}
