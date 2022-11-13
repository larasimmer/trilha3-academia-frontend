<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VersionsCmsPageCache\Controller\Adminhtml\Cms\Hierarchy;

use Magento\Cms\Api\GetPageByIdentifierInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Store\Model\Store;
use Magento\TestFramework\TestCase\AbstractBackendController;
use Magento\Framework\App\Cache\Type\Block as BlockCache;
use Magento\PageCache\Model\Cache\Type as PageCache;

/**
 * Checks save cms hierarchy
 *
 * @magentoAppArea adminhtml
 */
class SaveTest extends AbstractBackendController
{
    /** @var string */
    protected $uri = 'backend/admin/cms_hierarchy/save';

    /** @var GetPageByIdentifierInterface  */
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
     * @magentoDataFixture Magento/Cms/_files/pages.php
     * @magentoCache full_page enabled
     * @magentoCache block_html enabled
     * @return void
     */
    public function testCacheAfterHierarchySave(): void
    {
        $parentPage = $this->getPageByIdentifier->execute('page100', Store::DEFAULT_STORE_ID);

        $data = [
            'nodes_data' => json_encode([
                '_0' => [
                    'node_id' => '_0',
                    'page_id' => $parentPage->getId(),
                    'parent_node_id' => null,
                    'identifier' => 'node-1',
                    'scope' => 0,
                    'level' => 1,
                    'label' => 'Node 1',
                    'sort_order' => 3,
                    'top_menu_visibility' => 1,
                    'menu_visibility' => 1,
                    'pager_visibility' => 1,
                    'request_url' => $parentPage->getIdentifier(),
                ],
            ])
        ];
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue($data);
        $this->dispatch($this->uri);
        $cacheTypeList = $this->_objectManager->get(TypeListInterface::class);
        $invalidatedTypes = $cacheTypeList->getInvalidated();
        $this->assertSessionMessages($this->containsEqual(__('You have saved the hierarchy.')));
        $this->assertArrayHasKey(PageCache::TYPE_IDENTIFIER, $invalidatedTypes);
        $this->assertArrayHasKey(BlockCache::TYPE_IDENTIFIER, $invalidatedTypes);
    }
}
