<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VisualMerchandiser\Block\Adminhtml\Category\Merchandiser;

use Magento\Catalog\Model\Product;
use Magento\Framework\DB\Select;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\Website;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Indexer\TestCase;
use Magento\TestFramework\ObjectManager;
use Magento\VisualMerchandiser\Model\Position\Cache;

/**
 * @magentoAppArea adminhtml
 * @magentoDbIsolation disabled
 */
class TileTest extends TestCase
{
    const CATEGORY_ID = 333;
    const FIRST_WEBSITE_STORE = 1;
    const SECOND_WEBSITE_FIRST_STORE = 2;
    const SECOND_WEBSITE_SECOND_STORE = 3;

    /**
     * @var string
     */
    private $positionCacheKey;

    /**
     * @var Tile
     */
    private $tile;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var Cache
     */
    private $positionCache;

    public static function setUpBeforeClass(): void
    {
        $db = Bootstrap::getInstance()
            ->getBootstrap()
            ->getApplication()
            ->getDbInstance();
        if (!$db->isDbDumpExists()) {
            throw new \LogicException('DB dump does not exist.');
        }
        $db->restoreFromDbDump();

        parent::setUpBeforeClass();
    }

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $layout = $this->objectManager->get(LayoutInterface::class);

        /** @var Tile $tile */
        $this->tile = $layout->createBlock(
            Tile::class
        );
        $this->positionCacheKey = 'cache_key';
        $this->tile->setPositionCacheKey($this->positionCacheKey);

        $this->positionCache = $this->objectManager->get(Cache::class);
    }

    /**
     * @magentoDataFixture Magento/Store/_files/second_website_with_two_stores.php
     * @magentoDataFixture Magento/Catalog/_files/category.php
     * @magentoDataFixture Magento/VisualMerchandiser/Block/Adminhtml/Category/Merchandiser/_files/products_with_websites_and_stores.php
     */
    public function testGetPreparedCollection()
    {
        // The first website
        $this->tile->getRequest()->setParams(
            [
                'id' => self::CATEGORY_ID,
                'store' => $this->getStoreId(self::FIRST_WEBSITE_STORE)
            ]
        );
        $collection = $this->tile->getPreparedCollection();
        $products = $collection->getItems();
        $this->assertProducts(
            [
                'Simple Product',
                'Simple Product on both website',
            ],
            $products
        );

        // The second website the first store view
        $this->tile->getRequest()->setParams(
            [
                'id' => self::CATEGORY_ID,
                'store' => $this->getStoreId(self::SECOND_WEBSITE_FIRST_STORE)
            ]
        );
        $collection = $this->tile->getPreparedCollection();
        $products = $collection->getItems();
        $this->assertProducts(
            [
                'Simple Product on second website',
                'Simple Product on both website',
            ],
            $products
        );

        // The second website the second store view
        $this->tile->getRequest()->setParams(
            [
                'id' => self::CATEGORY_ID,
                'store' => $this->getStoreId(self::SECOND_WEBSITE_SECOND_STORE)
            ]
        );
        $collection = $this->tile->getPreparedCollection();
        $products = $collection->getItems();
        $this->assertProducts(
            [
                'Simple Product on second website',
                'Simple Product on both website',
            ],
            $products
        );

        // All websites
        $this->tile->getRequest()->setParams(
            [
                'id' => self::CATEGORY_ID,
                'store' => $this->getStoreId('no store')
            ]
        );
        $collection = $this->tile->getPreparedCollection();
        $products = $collection->getItems();
        $this->assertProducts(
            [
                'Simple Product on second website',
                'Simple Product on both website',
                'Simple Product',
                'Simple Product without website',
            ],
            $products
        );
    }

    /**
     * assert products
     *
     * @param $expectProductNames
     * @param $products
     */
    private function assertProducts($expectProductNames, $products)
    {
        $productNames = [];
        /** @var  $product */
        foreach ($products as $product) {
            $this->assertInstanceOf(Product::class, $product);
            /** @var Product $product */
            $productNames[] = $product->getName();
        }

        $this->assertEmpty(array_diff($expectProductNames, $productNames));
        $this->assertEmpty(array_diff($productNames, $expectProductNames));

        $this->positionCache->saveData($this->positionCacheKey, null);
    }

    /**
     * Get store id
     *
     * @param string $key
     * @return int|null
     */
    private function getStoreId($key)
    {
        switch ($key) {
            case self::FIRST_WEBSITE_STORE:
                /** @var Website $website */
                $website = $this->objectManager->create(Website::class);
                $storeIds = $website->load('1', 'website_id')
                    ->getStoreIds();
                return array_shift($storeIds);
            case self::SECOND_WEBSITE_FIRST_STORE:
                $store = $this->objectManager->create(Store::class);
                return $store->load('fixture_second_store', 'code')
                    ->getId();
            case self::SECOND_WEBSITE_SECOND_STORE:
                $store = $this->objectManager->create(Store::class);
                return $store->load('fixture_third_store', 'code')
                    ->getId();
            default:
                return null;
        }
    }

    /**
     * Verifies that collection select is ordered by position field but not product ids on a category loading
     *
     * @return void
     */
    public function testGetPreparedCollectionOrderBy(): void
    {
        $expectedOrderBy = [
            ['at_position.position', Select::SQL_ASC],
            ['e.entity_id', Select::SQL_DESC]
        ];
        $this->tile->getRequest()
            ->setParams(
                [
                    'id' => self::CATEGORY_ID,
                    'store' => $this->getStoreId(self::FIRST_WEBSITE_STORE)
                ]
            );
        $collection = $this->tile->getPreparedCollection();
        $orderBy = $collection->getSelect()
            ->getPart('order');

        $this->assertEquals($expectedOrderBy, $orderBy);
    }
}
