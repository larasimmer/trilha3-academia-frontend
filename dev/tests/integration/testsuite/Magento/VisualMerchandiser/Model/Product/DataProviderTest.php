<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VisualMerchandiser\Model\Product;

use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Zend_Json_Exception;

/**
 * Class DataProviderTest to test Visual Merchandiser's product data provider
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation disabled
 */
class DataProviderTest extends TestCase
{
    /**
     * Test for missing items of the Visual Merchandiser product grid sorting by price
     *
     * @param array $sortData
     * @magentoDataFixture Magento/Catalog/_files/multiple_products_with_few_out_of_stock.php
     * @dataProvider sortDataProvider
     * @throws Zend_Json_Exception
     */
    public function testGetDataSortByPrice(array $sortData)
    {
        $dataProvider = Bootstrap::getObjectManager()->create(
            DataProvider::class,
            [
                'name' => 'merchandiser_product_listing_data_source',
                'primaryFieldName' => 'entity_id',
                'requestFieldName' => 'id',
            ]
        );

        $dataProvider->addOrder($sortData['column'], $sortData['order']);

        $data = $dataProvider->getData();
        $this->assertEquals($data['totalRecords'], count($data['items']));
    }

    /**
     * Sorting information data provider
     *
     * @return array
     */
    public function sortDataProvider(): array
    {
        return [
            [
                [
                    'column' => 'price',
                    'order' => 'dsc'
                ]
            ],
            [
                [
                    'column' => 'sku',
                    'order' => 'asc'
                ]
            ],
        ];
    }
}
