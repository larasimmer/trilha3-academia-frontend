<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissions\Model\Indexer\Category\Action;

use Magento\Catalog\Model\CategoryFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\CatalogPermissions\Model\ResourceModel\Permission\Index as PermissionIndex;
use Magento\Indexer\Model\Indexer;
use Magento\Catalog\Model\Category;
use Magento\Framework\ObjectManagerInterface;
use Magento\CatalogPermissions\Model\Indexer\Category as PermissionIndexerCategory;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\Math\Random;
use Magento\Catalog\Helper\DefaultCategory;
use PHPUnit\Framework\TestCase;

/**
 * Category permissions test for rows action
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation disabled
 * @magentoAppIsolation enabled
 */
class RowsTest extends TestCase
{
    /**
     * @var PermissionIndex
     */
    private $permissionIndex;

    /**
     * @var Category
     */
    private $category;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /** @var  $indexer Indexer */
    private $indexer;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->permissionIndex = $this->objectManager->get(PermissionIndex::class);
        $this->categoryRepository = $this->objectManager->get(CategoryRepositoryInterface::class);
        $indexer = $this->objectManager->get(Indexer::class);
        $this->indexer = $indexer->load(PermissionIndexerCategory::INDEXER_ID);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        if ($this->category != null && !empty($this->category->getId())) {
            $this->categoryRepository->delete($this->category);
        }
        $this->indexer->setScheduled(false);
        $this->indexer->reindexAll();
    }

    /**
     * Test reindex row
     *
     * @return void
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/enabled 1
     * @magentoDataFixture Magento/CatalogPermissions/_files/category_with_permissions.php
     */
    public function testReindexRow(): void
    {
        $this->indexer->setScheduled(true);
        $this->indexer->reindexAll();

        $this->assertNotEmpty($this->permissionIndex->getIndexForCategory(333));

        $this->category = $this->createNewCategory();
        //use parent category ids with new category id for schedule indexer
        $this->indexer->reindexList(
            [
                $this->category->getParentId(),
                $this->category->getId(),
            ]
        );

        $this->assertNotEmpty($this->permissionIndex->getIndexForCategory(333));
    }

    /**
     * Create category
     *
     * @return CategoryInterface
     */
    private function createNewCategory(): CategoryInterface
    {
        $categoryFactory = $this->objectManager->get(CategoryFactory::class);
        $defaultCategory = $this->objectManager->get(DefaultCategory::class);
        /** @var Random $mathRandom */
        $name = $this->objectManager->get(Random::class)->getRandomString(8);
        /** @var CategoryInterface $model */
        $model = $categoryFactory->create();
        $model->setName($name)
            ->setParentId($defaultCategory->getId())
            ->setLevel(2)
            ->setIsActive(true)
            ->setPosition(1);
        $this->categoryRepository->save($model);

        return $model;
    }
}
