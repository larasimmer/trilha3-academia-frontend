<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Model\ResourceModel\Product\Price;

use Magento\Catalog\Api\Data\SpecialPriceInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * Test special prices
 *
 * @magentoAppArea adminhtml
 */
class SpecialPriceAdminhtmlTest extends TestCase
{
    /**
     * @var SpecialPrice
     */
    private $specialPrice;

    /**
     * @var SpecialPriceInterfaceFactory
     */
    private $specialPriceFactory;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->specialPrice = $objectManager->create(SpecialPrice::class);
        $this->specialPriceFactory = $objectManager->create(SpecialPriceInterfaceFactory::class);
        $this->productRepository = $objectManager->create(ProductRepositoryInterface::class);
        $this->storeRepository = $objectManager->get(StoreRepositoryInterface::class);
    }

    /**
     * Test special prices.
     *
     * Test case when special price has website scope end it has empty value in some store
     * and product is saved in this store after saving schedule
     *
     * @magentoDataFixture Magento/Catalog/_files/product_with_price_on_second_website.php
     * @magentoDbIsolation disabled
     *
     * @return void
     */
    public function testSpecialPricesAfterSaveProductAfterSaveSchedule(): void
    {
        $productSku = 'second-website-price-product';
        //set empty value for special price in second store
        $storeId = $this->storeRepository->get('fixture_second_store')->getId();
        $product = $this->productRepository->get($productSku, true, $storeId);
        $product->setSpecialPrice("");
        $this->productRepository->save($product);

        $updateDatetime = new \DateTime();
        $priceFrom = $updateDatetime->modify('+10 days')
            ->format('Y-m-d H:i:s');
        $priceTo = $updateDatetime->modify('+2 days')
            ->format('Y-m-d H:i:s');
        $prices[] = $this->specialPriceFactory->create()
            ->setSku($productSku)
            ->setStoreId($storeId)
            ->setPrice(8)
            ->setPriceFrom($priceFrom)
            ->setPriceTo($priceTo);
        $result = $this->specialPrice->update($prices);
        $this->assertTrue($result);
        //price data after save schedule
        $pricesData = $this->specialPrice->get([$productSku]);
        $this->assertCount(6, $pricesData);
        $product = $this->productRepository->get($productSku, true, $storeId);
        $product->setSpecialPrice("");
        $this->productRepository->save($product);
        //price data after save product
        $pricesDataAfterSave = $this->specialPrice->get([$productSku]);

        $this->assertCount(6, $pricesData);
        $this->assertEquals($pricesData, $pricesDataAfterSave);
    }
}
