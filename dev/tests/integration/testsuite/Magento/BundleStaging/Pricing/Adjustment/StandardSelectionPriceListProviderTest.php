<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\BundleStaging\Pricing\Adjustment;

use Magento\BundleStaging\Pricing\Adjustment\StandardSelectionPriceListProvider;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Test Selection Price List Provider
 *
 * @magentoAppArea adminhtml
 */
class StandardSelectionPriceListProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var StandardSelectionPriceListProvider
     */
    private $standardSelectionPriceListProvider;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->standardSelectionPriceListProvider = $this->objectManager->create(
            StandardSelectionPriceListProvider::class
        );
    }

    /**
     * Testing getting product price list
     *
     * @magentoDataFixture Magento/Bundle/_files/bundle_product_checkbox_options.php
     * @return void
     */
    public function testGetPriceList(): void
    {
        $fixtureProduct = [
            'sku' => 'bundle-product-checkbox-options',
            'expected_price_count' => 1,
            'expected_price' => 10,
        ];
        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $product = $productRepository->get($fixtureProduct['sku']);
        $priceLists = $this->standardSelectionPriceListProvider->getPriceList($product, true, false);
        $this->assertCount($fixtureProduct['expected_price_count'], $priceLists);
        $currentPrice = end($priceLists);
        $this->assertEquals($fixtureProduct['expected_price'], $currentPrice->getValue());
    }
}
