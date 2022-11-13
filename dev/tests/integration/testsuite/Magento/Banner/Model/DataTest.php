<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Banner\Model;

class DataTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Banner\Model\Banner\Data
     */
    private $bannersData;

    protected function setUp(): void
    {
        $this->bannersData = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
            \Magento\Banner\Model\Banner\Data::class
        );
    }

    /**
     * @magentoDataFixture Magento/Banner/_files/banner_disabled_40_percent_off.php
     * @magentoDataFixture Magento/Banner/_files/banner_enabled_40_to_50_percent_off.php
     * @magentoDataFixture Magento/Banner/_files/banner_catalog_rule.php
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     *
     * @magentoDbIsolation disabled
     * @magentoAppArea frontend
     */
    public function testGetSectionData()
    {
        $objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $objectManager->get(\Magento\Catalog\Model\Product::class)->loadByAttribute('sku', 'simple');
        $product->load($product->getId());
        $objectManager->get(\Magento\CatalogRule\Model\Indexer\IndexBuilder::class)->reindexById($product->getId());
        $banner = $objectManager->create(\Magento\Banner\Model\Banner::class);
        $banner->load('Test Dynamic Block', 'name');
        $data = $this->bannersData->getSectionData();
        $this->assertNotEmpty($data['items']['fixed']);
        $this->assertArrayHasKey($banner->getId(), $data['items']['fixed']);
    }
}
