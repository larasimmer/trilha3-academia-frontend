<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Model\ResourceModel\Item;

use Magento\Framework\ObjectManagerInterface;
use Magento\GiftRegistry\Model\EntityFactory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for Gift Registry items Collection.
 */
class CollectionTest extends TestCase
{
    /**
     * @var Collection
     */
    protected $_collection = null;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var EntityFactory
     */
    private $giftRegistryFactory;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->_collection = $this->objectManager->create(Collection::class);
        $this->giftRegistryFactory = $this->objectManager->get(EntityFactory::class);
    }

    public function testAddProductFilter()
    {
        $select = $this->_collection->getSelect();
        $this->assertSame([], $select->getPart(\Magento\Framework\DB\Select::WHERE));
        $this->assertSame($this->_collection, $this->_collection->addProductFilter(0));
        $this->assertSame([], $select->getPart(\Magento\Framework\DB\Select::WHERE));
        $this->_collection->addProductFilter(99);
        $where = $select->getPart(\Magento\Framework\DB\Select::WHERE);
        $this->assertArrayHasKey(0, $where);
        $this->assertStringContainsString('product_id', $where[0]);
        $this->assertStringContainsString('99', $where[0]);
    }

    public function testAddItemFilter()
    {
        $select = $this->_collection->getSelect();
        $this->assertSame([], $select->getPart(\Magento\Framework\DB\Select::WHERE));
        $this->assertSame($this->_collection, $this->_collection->addItemFilter(99));
        $this->_collection->addItemFilter([100, 101]);
        $this->assertStringMatchesFormat(
            '%AWHERE%S(%Sitem_id%S = %S99%S)%SAND%S(%Sitem_id%S IN(%S100%S,%S101%S))%A',
            (string)$select
        );
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDataFixture Magento/Catalog/_files/products.php
     * @magentoDataFixture Magento/GiftRegistry/_files/resource_item_collection.php
     */
    public function testGiftCollection()
    {
        $gr = $this->objectManager->get(\Magento\Framework\Registry::class)->registry('test_gift_registry');
        $product = $this->objectManager->get(\Magento\Framework\Registry::class)->registry('test_product');

        $collection = $this->objectManager->create(Collection::class);
        $collection->addRegistryFilter($gr->getId())->addWebsiteFilter();

        $this->assertTrue($collection->getSize() > 0);

        $relation = $this->objectManager->create(\Magento\Catalog\Model\Product\Website::class);
        $relation->removeProducts([1], [$product->getId()]);

        $collection = $this->objectManager->create(
            Collection::class
        )->addRegistryFilter(
            $gr->getId()
        )->addWebsiteFilter();

        $this->assertTrue($collection->getSize() == 0);
    }

    /**
     * Test if collection is properly loaded with product out of stock
     *
     * @magentoAppArea frontend
     * @magentoDataFixture Magento/GiftRegistry/_files/gift_registry_with_out_of_stock_product.php
     */
    public function testGiftRegistryCollectionWithOutOfStockProduct(): void
    {
        $giftRegistry = $this->giftRegistryFactory->create()->loadByUrlKey('gift_registry_birthday_type_url');
        $collection = $this->objectManager->create(Collection::class);
        $collection->addRegistryFilter($giftRegistry->getId());
        $items = $collection->getItems();
        $this->assertTrue(count($items) > 0);

        $options = reset($items)->getOptions();
        $this->assertTrue(count($options) > 0);
        $this->assertNotNull(reset($options)->getProduct());
        $this->assertEquals('simple-out-of-stock', reset($options)->getProduct()->getSku());
    }

    /**
     * Check that Collection is empty using Frontend scope.
     *
     * @return void
     * @magentoAppArea frontend
     * @magentoDataFixture Magento/GiftRegistry/_files/gift_registry_with_disabled_product.php
     */
    public function testLoadCollectionWithDisabledProductInFrontendScope(): void
    {
        $this->prepareAndVerifyCollection('gift_registry_birthday_type_url', 0);
    }

    /**
     * Check that Collection is NOT empty using Admin scope.
     *
     * @return void
     * @magentoAppArea adminhtml
     * @magentoDataFixture Magento/GiftRegistry/_files/gift_registry_with_disabled_product.php
     */
    public function testLoadCollectionWithDisabledProductInAdminScope(): void
    {
        $this->prepareAndVerifyCollection('gift_registry_birthday_type_url', 1);
    }

    /**
     * Filter Gift Registry items Collection by provided Url Key and verify its size.
     *
     * @param string $urlKey
     * @param int $expectedCount
     * @return void
     */
    private function prepareAndVerifyCollection(string $urlKey, int $expectedCount): void
    {
        $giftRegistry = $this->giftRegistryFactory->create()
            ->loadByUrlKey($urlKey);
        $this->_collection->addRegistryFilter($giftRegistry->getId())
            ->addWebsiteFilter();

        $this->assertCount($expectedCount, $this->_collection);
    }
}
