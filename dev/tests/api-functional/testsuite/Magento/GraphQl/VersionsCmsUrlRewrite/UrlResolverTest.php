<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\VersionsCmsUrlRewrite;

use Magento\Cms\Model\Page;
use Magento\CmsUrlRewrite\Model\CmsPageUrlRewriteGenerator;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\VersionsCms\Model\Hierarchy\Node;

/**
 * Test the GraphQL endpoint's `urlResolver` query for hierarchy nodes cms page.
 */
class UrlResolverTest extends GraphQlAbstract
{
    /** @var ObjectManager */
    private $objectManager;

    /**
     * @var string
     */
    private $scope = 'default';

    /**
     * @var int
     */
    private $scopeId = 0;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * @magentoApiDataFixture Magento/VersionsCmsUrlRewriteGraphQl/_files/hierarchy_nodes_cms_page.php
     */
    public function testHierarchyNodesCmsPageUrlResolver()
    {
        $nodeModel = $this->objectManager->create(
            Node::class,
            ['data' => ['scope' => $this->scope, 'scope_id' => $this->scopeId]]
        )->getHeritage();
        $nodesData = $nodeModel->getNodesData();
        $parentNodeData = reset($nodesData);
        $cmsNode = $nodeModel->loadByRequestUrl($parentNodeData['identifier'])
            ->getNodesCollection()
            ->getLastItem();
        /** @var Page $page */
        $pageModel = $this->objectManager->get(Page::class);
        $pageModel->load($cmsNode->getPageIdentifier());
        $requestPath = $cmsNode->getRequestUrl();

        $cmsPageId = $pageModel->getId();
        $cmsPageUrl = $pageModel->getIdentifier();
        $expectedEntityType = CmsPageUrlRewriteGenerator::ENTITY_TYPE;

        $query = $this->createQuery($requestPath);
        $response = $this->graphQlQuery($query);
        $this->assertEquals($cmsPageId, $response['urlResolver']['id']);
        $this->assertEquals($cmsPageUrl, $response['urlResolver']['relative_url']);
        $this->assertEquals(
            strtoupper(str_replace('-', '_', $expectedEntityType)),
            $response['urlResolver']['type']
        );
        $this->assertEquals(0, $response['urlResolver']['redirectCode']);
    }

    /**
     * @param string $path
     * @return string
     */
    private function createQuery(string $path): string
    {
        return <<<QUERY
{
  urlResolver(url:"{$path}") {
    id
    relative_url
    type
    redirectCode
  }
}
QUERY;
    }
}
