<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Staging;

use Magento\Integration\Api\AdminTokenServiceInterface;
use Magento\Staging\Model\ResourceModel\Update;
use Magento\Staging\Model\UpdateFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Preview catalog rule
 */
class PreviewCatalogRuleTest extends GraphQlAbstract
{
    /** @var AdminTokenServiceInterface */
    private $tokenService;

    /** @var UpdateFactory */
    private $updateFactory;

    /** @var Update */
    private $updateResourceModel;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->updateFactory = $objectManager->get(UpdateFactory::class);
        $this->updateResourceModel = $objectManager->get(Update::class);
        $this->tokenService = $objectManager->get(AdminTokenServiceInterface::class);
    }

    /**
     * Preview configurable and simple product after the disabled category and catalogRule get enabled in future update.
     *
     * @magentoApiDataFixture Magento/CatalogStaging/_files/disabled_categories.php
     * @magentoApiDataFixture Magento/CatalogStaging/_files/configurable_products.php
     * @magentoApiDataFixture Magento/CatalogStaging/_files/disabled_category_staged_for_update.php
     * @magentoApiDataFixture Magento/CatalogRuleStaging/_files/catalog_rule_for_category_ids_staged.php
     * @magentoApiDataFixture Magento/User/_files/user_with_custom_role.php
     */
    public function testPreviewConfigurableProductWithStagedCategoryAndCatalogRule()
    {
        $update = $this->updateFactory->create();
        $this->updateResourceModel->load($update, 'Test CatalogRule Update for Cat 8', 'name');

        $version = $update->getId();

        //admin token header
        $headerMap = $this->getHeaderMapWithAdminToken(
            'customRoleUser',
            \Magento\TestFramework\Bootstrap::ADMIN_PASSWORD
        );

        //preview version header
        $headerMap['Preview-Version'] = $version;
        // query for products belonging to the category 8 which includes both simple and configurable
        $query
            = <<<QUERY
            {
              products (filter:{category_id:{eq:"8"}},sort:{name: DESC}) {
                items {
                  sku
                  staged
                  name
                  price_range {
                    minimum_price {
                      final_price {
                        value
                      }
                      regular_price {
                        value
                      }
                    }
                    maximum_price {
                      final_price {
                        value
                      }
                      regular_price {
                        value
                      }
                    }
                  }
                }
              }
            }
QUERY;

        $response = $this->graphQlQuery($query);
        $this->assertArrayNotHasKey('errors', $response, 'Response has errors');
        $this->assertArrayHasKey('products', $response);
        $this->assertArrayHasKey('items', $response['products']);
        $this->assertCount(3, $response['products']['items']);

        $simpleProductNoPreview = $response['products']['items'][0];
        $this->assertEquals('Simple Product Eight', $simpleProductNoPreview['name']);
        $this->assertEquals(false, $simpleProductNoPreview['staged']);
        $firstConfigurableProductNoPreview = $response['products']['items'][2];
        $this->assertEquals('Configurable Product', $firstConfigurableProductNoPreview['name']);
        $this->assertEquals(false, $firstConfigurableProductNoPreview['staged']);
        // When not previewing, final and regular price should be equal
        $this->assertEquals(
            $simpleProductNoPreview['price_range']['minimum_price']['regular_price'],
            $simpleProductNoPreview['price_range']['minimum_price']['final_price']
        );
        $this->assertEquals(
            $firstConfigurableProductNoPreview['price_range']['minimum_price']['regular_price'],
            $firstConfigurableProductNoPreview['price_range']['minimum_price']['final_price']
        );

        $previewResponse = $this->graphQlQuery($query, [], '', $headerMap);
        $this->assertArrayNotHasKey('errors', $previewResponse, 'Preview response has errors');
        $this->assertArrayHasKey('products', $previewResponse);
        $this->assertArrayHasKey('items', $previewResponse['products']);
        $this->assertCount(3, $previewResponse['products']['items']);
        $previewSimpleProduct = $previewResponse['products']['items'][0];
        $this->assertEquals('Simple Product Eight', $previewSimpleProduct['name']);
        $this->assertEquals(true, $previewSimpleProduct['staged']);
        $previewConfigurableProduct = $previewResponse['products']['items'][2];
        $this->assertEquals('Configurable Product', $previewConfigurableProduct['name']);
        $this->assertEquals(true, $previewConfigurableProduct['staged']);

        // 50% discount is shown in preview
        $this->assertEquals(100, $previewSimpleProduct['price_range']['minimum_price']['regular_price']['value']);
        $this->assertEquals(50, $previewSimpleProduct['price_range']['minimum_price']['final_price']['value']);

        $this->assertEquals(10, $previewConfigurableProduct['price_range']['minimum_price']['regular_price']['value']);
        $this->assertEquals(5, $previewConfigurableProduct['price_range']['minimum_price']['final_price']['value']);
    }

    /**
     * Preview simple product after the assigned category and catalogRule get enabled in future update.
     *
     * @magentoApiDataFixture Magento/CatalogStaging/_files/disabled_categories.php
     * @magentoApiDataFixture Magento/CatalogStaging/_files/configurable_products.php
     * @magentoApiDataFixture Magento/CatalogStaging/_files/disabled_category_staged_for_update.php
     * @magentoApiDataFixture Magento/CatalogRuleStaging/_files/catalog_rule_for_category_ids_staged.php
     * @magentoApiDataFixture Magento/User/_files/user_with_custom_role.php
     */
    public function testPreviewSimpleProductWithStagedCategoryAndCatalogRule()
    {
        $update = $this->updateFactory->create();
        $this->updateResourceModel->load($update, 'Test CatalogRule Update for Cat 8', 'name');

        $version = $update->getId();

        //admin token header
        $headerMap = $this->getHeaderMapWithAdminToken(
            'customRoleUser',
            \Magento\TestFramework\Bootstrap::ADMIN_PASSWORD
        );

        //preview version header
        $headerMap['Preview-Version'] = $version;

        $query
            = <<<QUERY
            {
              products (filter:{sku:{eq:"simple-8"}}) {
                items {
                  sku
                  name
                  staged
                  price_range {
                    minimum_price {
                      final_price {
                        value
                      }
                      regular_price {
                        value
                      }
                    }
                    maximum_price {
                      final_price {
                        value
                      }
                      regular_price {
                        value
                      }
                    }
                  }
                }
              }
            }
QUERY;

        $response = $this->graphQlQuery($query);
        $this->assertArrayNotHasKey('errors', $response, 'Response has errors');
        $this->assertArrayHasKey('products', $response);

        $simpleProductNoPreview = $response['products']['items'][0];
        $this->assertEquals('Simple Product Eight', $simpleProductNoPreview['name']);
        $this->assertEquals(false, $simpleProductNoPreview['staged']);

        // When not previewing, final and regular price should be equal
        $this->assertEquals(
            $simpleProductNoPreview['price_range']['minimum_price']['regular_price'],
            $simpleProductNoPreview['price_range']['minimum_price']['final_price']
        );
        $previewResponse = $this->graphQlQuery($query, [], '', $headerMap);
        $this->assertArrayNotHasKey('errors', $previewResponse, 'Preview response has errors');
        $this->assertArrayHasKey('products', $previewResponse);

        $previewSimpleProduct = $previewResponse['products']['items'][0];
        $this->assertEquals('Simple Product Eight', $previewSimpleProduct['name']);
        $this->assertEquals(true, $previewSimpleProduct['staged']);

        // 50% discount on  preview version (with catalog rule coming to effect)
        $this->assertEquals(100, $previewSimpleProduct['price_range']['minimum_price']['regular_price']['value']);
        $this->assertEquals(50, $previewSimpleProduct['price_range']['minimum_price']['final_price']['value']);
    }

    /**
     * Preview simple product with staged catalog rule
     *
     * @magentoApiDataFixture Magento/Catalog/_files/categories.php
     * @magentoApiDataFixture Magento/CatalogRuleStaging/_files/catalog_rule_10_off_staged.php
     * @magentoApiDataFixture Magento/User/_files/user_with_custom_role.php
     */
    public function testPreviewSimpleProductWithStagedCatalogRule()
    {
        $update = $this->updateFactory->create();
        $this->updateResourceModel->load($update, 'Test Catalog Rule Update', 'name');
        $updateStart = $update->getStartTime();
        $version = strtotime($updateStart) + 3600;

        //admin token header
        $headerMap = $this->getHeaderMapWithAdminToken(
            'customRoleUser',
            \Magento\TestFramework\Bootstrap::ADMIN_PASSWORD
        );

        //preview version header
        $headerMap['Preview-Version'] = $version;

        $query
            = <<<QUERY
            {
              products (filter:{sku:{eq:"simple-4"}}) {
                items {
                  sku
                  name
                  staged
                  price_range {
                    minimum_price {
                      final_price {
                        value
                      }
                      regular_price {
                        value
                      }
                    }
                    maximum_price {
                      final_price {
                        value
                      }
                      regular_price {
                        value
                      }
                    }
                  }
                }
              }
            }
QUERY;

        $response = $this->graphQlQuery($query);
        $this->assertArrayNotHasKey('errors', $response, 'Response has errors');
        $previewResponse = $this->graphQlQuery($query, [], '', $headerMap);
        $this->assertArrayNotHasKey('errors', $previewResponse, 'Preview response has errors');

        $this->assertArrayHasKey('products', $response);
        $product = $response['products']['items'][0];
        $this->assertArrayHasKey('products', $previewResponse);
        $previewProduct = $previewResponse['products']['items'][0];

        $this->assertEquals('Simple Product Three', $product['name']);
        // product will not be staged until it is affected by the catalog rule
        $this->assertEquals(false, $product['staged']);

        // When not previewing, final and regular price should be equal
        $this->assertEquals(
            $product['price_range']['minimum_price']['regular_price'],
            $product['price_range']['minimum_price']['final_price']
        );

        // 10% discount is shown in preview
        $this->assertEquals(true, $previewProduct['staged']);
        $this->assertEquals('Simple Product Three', $previewProduct['name']);
        $this->assertEquals(10, $previewProduct['price_range']['minimum_price']['regular_price']['value']);
        $this->assertEquals(9, $previewProduct['price_range']['minimum_price']['final_price']['value']);
    }

    /**
     * Preview a staged simple product that also has a staged rule applied on it
     *
     * Product update starts a day after catalog rule update
     *
     * @magentoApiDataFixture Magento/CatalogStaging/_files/simple_product_staged_changes_2.php
     * @magentoApiDataFixture Magento/CatalogRuleStaging/_files/catalog_rule_10_off_staged.php
     * @magentoApiDataFixture Magento/User/_files/user_with_custom_role.php
     */
    public function testPreviewStagedSimpleProductWithStagedCatalogRule()
    {
        $ruleUpdate = $this->updateFactory->create();
        // load catalog rule update
        $this->updateResourceModel->load($ruleUpdate, 'Test Catalog Rule Update', 'name');
        $catalogRuleUpdateVersion = $ruleUpdate->getId();

        // load simple product update
        $simpleProductUpdate = $this->updateFactory->create();
        $this->updateResourceModel->load(
            $simpleProductUpdate,
            'Simple Product Update After CatalogRule update',
            'name'
        );
        $productUpdateVersion = $simpleProductUpdate->getId();

        //admin token header
        $headerMap = $this->getHeaderMapWithAdminToken(
            'customRoleUser',
            \Magento\TestFramework\Bootstrap::ADMIN_PASSWORD
        );

        //preview version header
        $headerMap['Preview-Version'] = $catalogRuleUpdateVersion;

        $query
            = <<<QUERY
            {
              products (filter:{sku:{eq:"asimpleproduct"}}) {
                items {
                  sku
                  name
                  staged
                  price_range {
                    minimum_price {
                      final_price {
                        value
                      }
                      regular_price {
                        value
                      }
                    }
                    maximum_price {
                      final_price {
                        value
                      }
                      regular_price {
                        value
                      }
                    }
                  }
                }
              }
            }
QUERY;

        // preview of product with catalog rule version_id
        $previewResponseWithCatalogRuleVersion = $this->graphQlQuery($query, [], '', $headerMap);
        $this->assertArrayNotHasKey('errors', $previewResponseWithCatalogRuleVersion, 'Preview response has errors');
        $previewProduct = $previewResponseWithCatalogRuleVersion['products']['items'][0];

        // preview of product with catalog rule version => 10% discount on product's original price
        $this->assertEquals(true, $previewProduct['staged']);
        $this->assertEquals(10, $previewProduct['price_range']['minimum_price']['regular_price']['value']);
        $this->assertEquals(9, $previewProduct['price_range']['minimum_price']['final_price']['value']);
        $this->assertEquals(10, $previewProduct['price_range']['maximum_price']['regular_price']['value']);
        $this->assertEquals(9, $previewProduct['price_range']['maximum_price']['final_price']['value']);
        $this->assertEquals('A Simple Product Name', $previewProduct['name']);

        $headerMap['Preview-Version'] = $productUpdateVersion;
        // preview of product with product update versionId
        $previewResponseWithProuctUpdateVersion = $this->graphQlQuery($query, [], '', $headerMap);
        $this->assertArrayNotHasKey('errors', $previewResponseWithProuctUpdateVersion, 'Preview response has errors');
        $previewProduct = $previewResponseWithProuctUpdateVersion['products']['items'][0];

        $this->assertEquals('Updated A Simple Product Name', $previewProduct['name']);

        // preview with product’s version_id => 10% rule applied on top of product’s adjusted price
        $this->assertEquals(6, $previewProduct['price_range']['minimum_price']['regular_price']['value']);
        $this->assertEquals(5.4, $previewProduct['price_range']['minimum_price']['final_price']['value']);
        $this->assertEquals(6, $previewProduct['price_range']['maximum_price']['regular_price']['value']);
        $this->assertEquals(5.4, $previewProduct['price_range']['maximum_price']['final_price']['value']);
    }

    /**
     * Get admin access token for Authorization
     *
     * @param string $username
     * @param string $password
     * @return array
     */
    private function getHeaderMapWithAdminToken(
        string $username,
        string $password
    ): array {
        $adminToken = $this->tokenService->createAdminAccessToken(
            $username,
            $password
        );
        $headerMap = ['Authorization' => 'Bearer ' . $adminToken];
        return $headerMap;
    }
}
