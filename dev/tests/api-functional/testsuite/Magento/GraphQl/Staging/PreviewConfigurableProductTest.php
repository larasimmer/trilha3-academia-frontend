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
 * Preview Configurable product
 */
class PreviewConfigurableProductTest extends GraphQlAbstract
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
        $this->tokenService = $objectManager->get(AdminTokenServiceInterface::class);
        $this->updateFactory = $objectManager->get(UpdateFactory::class);
        $this->updateResourceModel = $objectManager->get(Update::class);
    }

    /**
     * Preview Configurable product with a staging update
     *
     * @magentoApiDataFixture Magento/CatalogStaging/_files/configurable_product_staged_changes.php
     * @magentoApiDataFixture Magento/User/_files/user_with_custom_role.php
     */
    public function testPreviewConfigurableProduct()
    {
        $update = $this->updateFactory->create();
        $this->updateResourceModel->load($update, 'Configurable Product Update Test', 'name');
        $version = $update->getId();

        $headerMap = $this->getHeaderMapWithAdminToken(
            'customRoleUser',
            \Magento\TestFramework\Bootstrap::ADMIN_PASSWORD
        );

        //preview version header
        $headerMap['Preview-Version'] = $version;

        $query
            = <<<QUERY
{
  products(filter: {sku: {eq: "configurable"}}) {
    items {
      id
      attribute_set_id
      name
      sku
      price_range{
        maximum_price{
         final_price{value}
         regular_price{value}
        }
        minimum_price{
          final_price{value}
          regular_price{value}
        }
      }
      ... on ConfigurableProduct {
        configurable_options {
          id
          attribute_id
          label
          position
          use_default
          attribute_code
          values {
            value_index
            label
            store_label
            default_label
            use_default_value
          }
          product_id
        }
        variants {
          product {
            id
            name
            sku
            attribute_set_id
            categories {
              id
            }
          
          }
          attributes {
            label
            code
            value_index
          }
        }
      }
    }
  }
}
QUERY;
        $response = $this->graphQlQuery($query, [], '', $headerMap);
        $this->assertArrayNotHasKey('errors', $response, 'Response has errors');
        $this->assertArrayHasKey('products', $response);
        $this->assertNotEmpty($response['products']['items'], 'No product returned');
        $configurableProduct = $response['products']['items'][0];

        // preview response returns the updated name for the product
        $this->assertEquals('Updated Configurable Product Name', $configurableProduct['name']);
        $this->assertEquals(20, $configurableProduct['price_range']['maximum_price']['final_price']['value']);
        $this->assertEquals(20, $configurableProduct['price_range']['maximum_price']['regular_price']['value']);

        $this->assertEquals(10, $configurableProduct['price_range']['minimum_price']['final_price']['value']);
        $this->assertEquals(10, $configurableProduct['price_range']['minimum_price']['regular_price']['value']);

        $this->assertNotEmpty($configurableProduct['variants'], 'Variants are empty');
        $this->assertNotEmpty($configurableProduct['configurable_options'], 'configurable options not available');
        $this->assertCount(2, $configurableProduct['variants']);
        $configurableProductAttributeVariants = $configurableProduct['variants'];
        $this->assertEquals('simple_10', $configurableProductAttributeVariants[0]['product']['sku']);
        $this->assertEquals('simple_20', $configurableProductAttributeVariants[1]['product']['sku']);

        $this->assertEquals('Option 1', $configurableProductAttributeVariants[0]['attributes'][0]['label']);
        $this->assertEquals('Option 2', $configurableProductAttributeVariants[1]['attributes'][0]['label']);
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
