<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VisualMerchandiser\Plugin\Catalog\Model\ResourceModel\Product;

use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\Catalog\Model\ResourceModel\Category as CategoryResourceModel;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 */
class RankCategoryProductPositionsTest extends TestCase
{
    /**
     * Name of first product.
     */
    private const PRODUCT_1_NAME = 'product1';

    /**
     * Name of second product.
     */
    private const PRODUCT_2_NAME = 'product2';

    /**
     * @var ObjectManagerInterface
     */
    private $om;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var CategoryLinkManagementInterface
     */
    private $categoryLinkManagement;

    /**
     * @var CategoryResourceModel
     */
    private $categoryResourceModel;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        $this->om = Bootstrap::getObjectManager();
        $this->productRepository = $this->om->get(ProductRepositoryInterface::class);
        $this->categoryResourceModel = $this->om->get(CategoryResourceModel::class);
        $this->categoryLinkManagement = $this->om->create(CategoryLinkManagementInterface::class);
    }

    /**
     * @inheritDoc
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function tearDown(): void
    {
        $this->productRepository->delete($this->productRepository->get(self::PRODUCT_1_NAME));
        $this->productRepository->delete($this->productRepository->get(self::PRODUCT_2_NAME));
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/category.php
     *
     * @return void
     *
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function testAfterSaveCategoryLinks()
    {
        $categoryId = 333;

        $product1 = $this->createProduct(self::PRODUCT_1_NAME, $categoryId);
        $this->assertSame(-1, $this->getCategoryProductPosition((int) $product1->getId(), $categoryId));

        $product2 = $this->createProduct(self::PRODUCT_2_NAME, $categoryId);
        $this->assertSame(-1, $this->getCategoryProductPosition((int) $product1->getId(), $categoryId));
        $this->assertSame(-2, $this->getCategoryProductPosition((int) $product2->getId(), $categoryId));
    }

    /**
     * Create new product assigned to category.
     *
     * @param string $productName
     * @param int $categoryId
     * @return \Magento\Catalog\Api\Data\ProductInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\StateException
     */
    private function createProduct(string $productName, int $categoryId)
    {
        $product = Bootstrap::getObjectManager()->create(Product::class);
        $product->setTypeId(ProductType::TYPE_SIMPLE)
            ->setAttributeSetId(4)
            ->setName($productName)
            ->setSku($productName)
            ->setPrice(10);
        $product->setCategoryIds([$categoryId]);

        return $this->productRepository->save($product);
    }

    /**
     * Retrieve category product position.
     *
     * @param int $productId
     * @param int $categoryId
     * @return int
     */
    private function getCategoryProductPosition(int $productId, int $categoryId): int
    {
        $select = $this->categoryResourceModel->getConnection()->select();
        $select->from(
            $this->categoryResourceModel->getCategoryProductTable(),
            ['position']
        );
        $select->where('product_id = ?', $productId);
        $select->where('category_id = ?', $categoryId);

        return (int) $this->categoryResourceModel->getConnection()->fetchOne($select);
    }
}
