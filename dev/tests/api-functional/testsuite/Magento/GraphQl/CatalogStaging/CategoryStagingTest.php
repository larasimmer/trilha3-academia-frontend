<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\CatalogStaging;

use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test for category with staging
 *
 * Preconditions:
 *   Fixture with two stores, one category and staging update is created
 * Steps:
 * Set Headers - Store = default
 * Send Request:
 * {
 *   category(id: %categoryId%){
 *     id
 *     include_in_menu
 *     name
 *     image
 *     description
 *     display_mode
 *     is_anchor
 *     available_sort_by
 *     default_sort_by
 *     url_key
 *     meta_title
 *     meta_keywords
 *     meta_description
 *     products {
 *       page_info {
 *       total_pages
 *       }
 *       total_count
 *       items {
 *       __typename
 *       sku
 *       name
 *       url_key
 *       updated_at
 *       }
 *     }
 *  }
 * }
 * Expected response:
 * {
 *   "data": {
 *     "category": {
 *       "id": %categoryId%,
 *       "include_in_menu": 1,
 *       "name": "Category_en Updated",
 *       "image": null,
 *       "description": "<p>Category_en Description Updated</p>",
 *       "display_mode": "PAGE",
 *       "is_anchor": 1,
 *       "available_sort_by": [
 *         "position",
 *        "price"
 *       ],
 *       "default_sort_by": "position",
 *       "url_key": "category-en-Updated",
 *       "meta_title": "Category_en Meta Title Updated",
 *       "meta_keywords": "Category_en Meta Keywords Updated",
 *       "meta_description": "Category_en Meta Description Updated",
 *       "products": {
 *          "page_info": { total_pages: 1 },
 *          "total_count": 1
 *           "items": [
 *           {
 *               "sku": "prod2",
 *               "name": "prod2 st"
 *           }
 *         ]
 *      }
 *     }
 *   }
 * }
 */
class CategoryStagingTest extends GraphQlAbstract
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * Verify that request returns correct values for given category
     *
     * @magentoApiDataFixture Magento/CatalogStaging/_files/category_staging.php
     * @param string $query
     * @param string $storeCode
     * @param array $category
     * @return void
     * @throws \Exception
     * @dataProvider categoryStagingDataProvider
     */
    public function testCategoryStaging(string $query, string $storeCode, array $category): void
    {
        $response = $this->graphQlQuery($query, [], '', ['store' => $storeCode]);

        // check are there any items in the return data
        self::assertNotNull($response['category'], 'category must not be null');

        // check entire response
        $this->assertResponseFields($response, $category);
    }

    /**
     * Data provider for enabled category
     *
     * @return array
     */
    public function categoryStagingDataProvider(): array
    {
        return [
            [
                'query' => $this->getQuery(15),
                'store' => 'default',
                'data' => [
                    'category' => [
                        'id' => 15,
                        'include_in_menu' => 1,
                        'name' => 'Category_en Updated',
                        'image' => null,
                        'description' => '<p>Category_en Description Updated</p>',
                        'display_mode' => 'PAGE',
                        'is_anchor' => 1,
                        'available_sort_by' => ['position'],
                        'default_sort_by' => 'position',
                        'url_key' => 'category-en-Updated',
                        'meta_title' => 'Category_en Meta Title Updated',
                        'meta_keywords' => 'Category_en Meta Keywords Updated',
                        'meta_description' => 'Category_en Meta Description Updated',
                        'products' => [
                            'page_info' => [
                                'total_pages' => 0
                            ],
                            'total_count' => 0,
                            'items' => []
                        ]
                    ],
                ],
            ],
        ];
    }

    /**
     * return GraphQL query string by categoryId
     * @param int $categoryId
     * @return string
     */
    private function getQuery(int $categoryId): string
    {
        return <<<QUERY
{
 category(id: {$categoryId}){
    id
    include_in_menu
    name
    image
    description
    display_mode
    is_anchor
    available_sort_by
    default_sort_by
    url_key
    meta_title
    meta_keywords
    meta_description
    products {
      page_info {
        total_pages
      }
      total_count
      items {
        __typename
        sku
        name
        url_key
      }
    }
  }
}
QUERY;
    }
}
