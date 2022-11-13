<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Model;

use Magento\Catalog\Api\CategoryListInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\GraphQl\Service\GraphQlRequest;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Integration test for category tree data provider.
 *
 * @magentoAppArea graphql
 * @magentoDbIsolation disabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CategoryTreeTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var CategoryFactory
     */
    private $categoryFactory;

    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var CategoryListInterface
     */
    private $categoryList;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @var EntityMetadataInterface
     */
    private $categoryMetadata;

    /**
     * @var GraphQlRequest
     */
    private $graphQlRequest;

    /**
     * @var SerializerInterface
     */
    private $jsonSerializer;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->registry = $this->objectManager->get(Registry::class);
        $this->categoryFactory = $this->objectManager->get(CategoryFactory::class);
        $this->categoryRepository = $this->objectManager->get(CategoryRepositoryInterface::class);
        $this->searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        $this->categoryList = $this->objectManager->get(CategoryListInterface::class);
        $this->resourceConnection = $this->objectManager->get(ResourceConnection::class);
        $this->metadataPool = $this->objectManager->get(MetadataPool::class);
        $this->categoryMetadata = $this->metadataPool->getMetadata(CategoryInterface::class);
        $this->graphQlRequest = $this->objectManager->create(GraphQlRequest::class);
        $this->jsonSerializer = $this->objectManager->get(SerializerInterface::class);
    }

    /**
     * Category tree data provider returns all child categories.
     *
     * It tests that after deleting category with staging update
     * and re-creating category with few child categories
     * the CategoryTree::getTree() method returns all the child categories by GraphQl query.
     *
     * @magentoDataFixture Magento/CatalogStaging/_files/category_with_staging_update.php
     * @return void
     */
    public function testCategoryTreeReturnsAllChildCategories(): void
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('name', 'Category with staging update')->create();
        $categoryWithStaging = current($this->categoryList->getList($searchCriteria)->getItems());
        $this->deleteCategory($categoryWithStaging);

        // Recreate subcategory
        $subCategory = $this->categoryFactory->create();
        $subCategory->setName('Recreated Subcategory')
            ->setParentId(2)
            ->setIsActive(true);
        $this->categoryRepository->save($subCategory);

        // Create a few child categories for subcategory
        $subCategoryId = (int)$subCategory->getId();
        $firstChildCategory = $this->categoryFactory->create();
        $firstChildCategory->setName('Child category 1')
            ->setParentId($subCategoryId)
            ->setIsActive(true);
        $this->categoryRepository->save($firstChildCategory);

        $secondChildCategory = $this->categoryFactory->create();
        $secondChildCategory->setName('Child category 2')
            ->setParentId($subCategoryId)
            ->setIsActive(true);
        $this->categoryRepository->save($secondChildCategory);

        $this->assertLinkFieldNotEqualToPrimaryField($subCategoryId);

        $graphQlQuery = <<<QUERY
{
    category(id: {$subCategoryId}) {
        id
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
        children {
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
            id
            name
            url_key
            url_path
            children_count
            path
            image
            productImagePreview: products(pageSize: 1) {
                items {
                    small_image {
                      label
                      url
                    }
                }
            }
        }
    }
}
QUERY;
        $response = $this->graphQlRequest->send($graphQlQuery);
        $responseData = $this->jsonSerializer->unserialize($response->getContent());

        // Checks the child categories are returned by GraphQl query
        $this->assertArrayNotHasKey('errors', $responseData);
        $this->assertArrayHasKey('data', $responseData);
        $firstChildCategoryIdFromResponse = $responseData['data']['category']['children'][0]['id'] ?? 0;
        $secondChildCategoryIdFromResponse = $responseData['data']['category']['children'][1]['id'] ?? 0;
        $this->assertEquals($firstChildCategory->getId(), $firstChildCategoryIdFromResponse);
        $this->assertEquals($secondChildCategory->getId(), $secondChildCategoryIdFromResponse);
    }

    /**
     * Deletes category.
     *
     * @param CategoryInterface $category
     * @return void
     */
    private function deleteCategory(CategoryInterface $category): void
    {
        try {
            $this->registry->unregister('isSecureArea');
            $this->registry->register('isSecureArea', true);
            $this->categoryRepository->delete($category);
        } finally {
            $this->registry->unregister('isSecureArea');
            $this->registry->register('isSecureArea', false);
        }
    }

    /**
     * Checks that entity ids do not equal to row ids.
     *
     * @param int $categoryId
     * @return void
     */
    private function assertLinkFieldNotEqualToPrimaryField(int $categoryId): void
    {
        $linkField = $this->categoryMetadata->getLinkField();
        $identifierField = $this->categoryMetadata->getIdentifierField();
        $parentIdField = CategoryInterface::KEY_PARENT_ID;
        $connection = $this->resourceConnection->getConnection();
        $query = $connection->select()
            ->from($this->categoryMetadata->getEntityTable(), 'COUNT(*)')
            ->where("{$identifierField} = ? OR {$parentIdField} = ?", $categoryId)
            ->where("{$linkField} = {$identifierField}")
            ->setPart('disable_staging_preview', true);
        $selectResult = $connection->fetchOne($query);
        $this->assertEquals(0, $selectResult);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('name', ['Recreated Subcategory', 'Child category 1', 'Child category 2'], 'in')
            ->create();
        $categories = $this->categoryList->getList($searchCriteria)->getItems();
        try {
            foreach ($categories as $category) {
                $this->deleteCategory($category);
            }
        } catch (\Throwable $e) {
            // Nothing to delete
        }
    }
}
