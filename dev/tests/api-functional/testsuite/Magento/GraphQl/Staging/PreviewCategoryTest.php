<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Staging;

use Magento\Integration\Api\AdminTokenServiceInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\Staging\Model\ResourceModel\Update;
use Magento\Staging\Model\UpdateFactory;

/**
 * Preview Category staging test
 */
class PreviewCategoryTest extends GraphQlAbstract
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

    /**
     * Preview CategoryList with name change on schedule update
     *
     * @magentoApiDataFixture Magento/CatalogStaging/_files/category_staged_preview_disabled.php
     * @magentoApiDataFixture Magento/Store/_files/second_store.php
     * @magentoApiDataFixture Magento/User/_files/user_with_custom_role.php
     */
    public function testPreviewCategoryList()
    {
        $update = $this->updateFactory->create();
        $this->updateResourceModel->load($update, 'Preview Category Staging', 'name');
        $version = $update->getId();
        $categoryId = 333;
        //admin token header
        $headerMap = $this->getHeaderMapWithAdminToken(
            'customRoleUser',
            \Magento\TestFramework\Bootstrap::ADMIN_PASSWORD
        );

        //preview version header
        $headerMap['Preview-Version']  =  $version;

        $query
            = <<<QUERY
{
    categoryList(filters: {ids: {in: ["$categoryId"]}}){
        id
        name
        staged
        image
        url_key
        url_path
        description
        products{
          total_count
          items{
            name
            sku
          }
        }
        children{
          name
          staged
          image
          url_key
          description
          products{
            total_count
            items{
              name
              sku
            }
          }
          children{
            name
            staged
            image
            children{
              name
              image
            }
          }
        }
    }
}
QUERY;
        $response = $this->graphQlQuery($query, [], '', $headerMap);
        $this->assertArrayNotHasKey('errors', $response, 'Response has errors');
        $this->assertArrayHasKey('categoryList', $response);
        $this->assertEquals('new category update', $response["categoryList"][0]["name"]);
        $this->assertEquals(true, $response["categoryList"][0]["staged"]);

        // requesting category with different store
        $headerMap['store'] = 'fixture_second_store';
        $response = $this->graphQlQuery($query, [], '', $headerMap);
        $this->assertArrayNotHasKey('errors', $response, 'Response has errors');
        $this->assertArrayHasKey('categoryList', $response);
        $this->assertEquals('Category 1', $response["categoryList"][0]["name"]);

        // requesting category without headers
        $response = $this->graphQlQuery($query, [], '', []);
        $this->assertArrayNotHasKey('errors', $response, 'Response has errors');
        $this->assertArrayHasKey('categoryList', $response);
        $this->assertEquals('Category 1', $response["categoryList"][0]["name"]);
    }

    /**
     * Preview Category with name change on schedule update
     *
     * @magentoApiDataFixture Magento/CatalogStaging/_files/category_staged_preview_disabled.php
     * @magentoApiDataFixture Magento/Store/_files/second_store.php
     * @magentoApiDataFixture Magento/User/_files/user_with_custom_role.php
     */
    public function testPreviewCategory()
    {
        $update = $this->updateFactory->create();
        $this->updateResourceModel->load($update, 'Preview Category Staging', 'name');
        $version = $update->getId();
        $categoryId = 333;
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
    category(id: $categoryId){
        id
        name
        staged
        image
        url_key
        url_path
        description
        products{
          total_count
          items{
            name
            sku
          }
        }
        children{
          name
          image
          url_key
          description
          products{
            total_count
            items{
              name
              sku
            }
          }
          children{
            name
            image
            children{
              name
              image
            }
          }
        }
    }
}
QUERY;
        $response = $this->graphQlQuery($query, [], '', $headerMap);
        $this->assertArrayNotHasKey('errors', $response, 'Response has errors');
        $this->assertArrayHasKey('category', $response);
        $this->assertEquals('new category update', $response["category"]["name"]);
        $this->assertEquals(true, $response["category"]["staged"]);

        // requesting category with different store
        $headerMap['store'] = 'fixture_second_store';
        $response = $this->graphQlQuery($query, [], '', $headerMap);
        $this->assertArrayNotHasKey('errors', $response, 'Response has errors');
        $this->assertArrayHasKey('category', $response);
        $this->assertEquals('Category 1', $response["category"]["name"]);
        $this->assertEquals(true, $response["category"]["staged"]);

        // requesting category without headers
        $response = $this->graphQlQuery($query, [], '', []);
        $this->assertArrayNotHasKey('errors', $response, 'Response has errors');
        $this->assertArrayHasKey('category', $response);
        $this->assertEquals('Category 1', $response["category"]["name"]);
    }

    /**
     * Preview CategoryList with disabled category on schedule update
     *
     * @magentoApiDataFixture Magento/CatalogStaging/_files/category_staged_preview_disabled.php
     * @magentoApiDataFixture Magento/User/_files/user_with_custom_role.php
     */
    public function testPreviewDisabledCategory()
    {
        $update = $this->updateFactory->create();
        $this->updateResourceModel->load($update, 'Preview Disabled Category Staging', 'name');
        $version = $update->getId();
        $categoryId = 333;
        //admin token header
        $headerMap = $this->getHeaderMapWithAdminToken(
            'customRoleUser',
            \Magento\TestFramework\Bootstrap::ADMIN_PASSWORD
        );

        //preview version header
        $headerMap['Preview-Version']  =  $version;

        $query
            = <<<QUERY
{
    categoryList(filters: {ids: {in: ["$categoryId"]}}){
        id
        name
        staged
        image
        url_key
        url_path
        description
        products{
          total_count
          items{
            name
            sku
          }
        }
        children{
          name
          image
          url_key
          description
          products{
            total_count
            items{
              name
              sku
            }
          }
          children{
            name
            image
            children{
              name
              image
            }
          }
        }
    }
}
QUERY;
        $response = $this->graphQlQuery($query, [], '', $headerMap);
        $this->assertArrayNotHasKey('errors', $response, 'Response has errors');
        $this->assertArrayHasKey('categoryList', $response);
        $this->assertArrayNotHasKey(0, $response["categoryList"]);
        $this->assertEmpty($response["categoryList"], 'category is not empty');
    }
}
