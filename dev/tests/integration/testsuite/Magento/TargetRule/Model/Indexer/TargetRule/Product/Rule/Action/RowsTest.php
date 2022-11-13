<?php
/**
 * @category    Magento
 * @package     Magento_TargetRule
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Action;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule as ProductRule;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product;
use Magento\TestFramework\Indexer\TestCase;
use Magento\TargetRule\Model\Rule;
use Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Processor;
use Magento\Framework\ObjectManagerInterface;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Helper\DefaultCategory;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TargetRule\Model\ResourceModel\Rule as TargetRuleResourceModel;
use Magento\TargetRule\Model\ResourceModel\Rule\Collection;

/**
 * Class for test target rule product indexer
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @magentoAppArea adminhtml
 */
class RowsTest extends TestCase
{
    /**
     * @var Processor
     */
    protected $_processor;

    /**
     * @var Rule
     */
    protected $_rule;

    /**
     * @var Product
     */
    protected $_product;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var array $productSkus
     */
    private $productSkus = [];

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->_processor = $this->objectManager->get(Processor::class);
        $this->_rule = $this->objectManager->get(Rule::class);
        $this->_product = $this->objectManager->create(Product::class);
        $this->productRepository = $this->objectManager->create(ProductRepositoryInterface::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        foreach ($this->productSkus as $productSku) {
            try {
                $this->productRepository->deleteById($productSku);
            } catch (NoSuchEntityException $e) {
                //Product already removed
            }
        }
        parent::tearDown();
    }

    /**
     * Test reindex target rule
     *
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Catalog/controllers/_files/products.php
     * @magentoDataFixture Magento/TargetRule/_files/related.php
     */
    public function testReindexRows()
    {
        $this->_processor->getIndexer()->setScheduled(false);
        $this->assertFalse($this->_processor->getIndexer()->isScheduled());

        $this->_product->setTypeId(Type::TYPE_SIMPLE)
            ->setAttributeSetId(4)
            ->setWebsiteIds([1])
            ->setSku('simple_product_3')
            ->setName('Simple Product 3 Name')
            ->setDescription('Simple Product 3 Full Description')
            ->setShortDescription('Simple Product 3 Short Description')
            ->setPrice(987.65)
            ->setTaxClassId(2)
            ->setStockData(['use_config_manage_stock' => 1, 'qty' => 24, 'is_in_stock' => 1])
            ->setVisibility(Visibility::VISIBILITY_BOTH)
            ->setStatus(Status::STATUS_ENABLED)
            ->save();

        /**
         * @var \Magento\Catalog\Model\ResourceModel\Product $productRepository
         */
        $productRepository = $this->_product->getResource();
        $this->_processor->reindexList(
            [
                $productRepository->getIdBySku('simple_product_2'),
                $productRepository->getIdBySku('simple_product_3')
            ]
        );

        $this->_rule->load(1);
        $this->assertCount(3, $this->_rule->getMatchingProductIds());
    }

    /**
     * Test target product rule with indexer on schedule
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Catalog/controllers/_files/products.php
     * @magentoDataFixture Magento/TargetRule/_files/related.php
     */
    public function testReindexRowsWithScheduleAndDisabledProduct()
    {
        $productData = [
            ProductInterface::NAME => 'Simple Product 4 Name',
            ProductInterface::SKU => 'simple_product_4',
            ProductInterface::STATUS => Status::STATUS_DISABLED
        ];
        $this->_processor->getIndexer()->setScheduled(true);
        $this->assertTrue($this->_processor->getIndexer()->isScheduled());
        $targetProductRuleIndex = $this->objectManager->get(ProductRule::class);
        $product = $this->createProduct($productData);
        $this->productSkus[] = $product->getSku();
        $targetProductRuleIndex->executeList(
            [$product->getId()]
        );
        $targetRuleCollection = $this->objectManager->get(Collection::class);
        $items = $targetRuleCollection->addFieldToFilter('name', 'related')
            ->getItems();
        $rule = array_shift($items);
        $ruleResource = $this->objectManager->get(TargetRuleResourceModel::class);
        $productAssociatedEntityIds = $ruleResource->getAssociatedEntityIds($rule->getId(), 'product');
        $this->assertCount(2, $productAssociatedEntityIds);
    }

    /**
     * Create product by data
     *
     * @param array $productData
     */
    private function createProduct(array $productData)
    {
        /** @var WebsiteRepositoryInterface $websiteRepository */
        $websiteRepository = $this->objectManager->get(WebsiteRepositoryInterface::class);
        $defaultWebsiteId = $websiteRepository->get('base')->getId();
        /** @var DefaultCategory $defaultCategory */
        $defaultCategory = $this->objectManager->get(DefaultCategory::class);
        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $this->objectManager->create(ProductRepositoryInterface::class);
        /** @var ProductInterfaceFactory $productFactory */
        $productFactory = $this->objectManager->get(ProductInterfaceFactory::class);
        $product = $productFactory->create();
        $productData = [
            ProductInterface::TYPE_ID => Type::TYPE_SIMPLE,
            ProductInterface::ATTRIBUTE_SET_ID => $product->getDefaultAttributeSetId(),
            ProductInterface::SKU => $productData['sku'],
            ProductInterface::NAME => $productData['name'],
            ProductInterface::PRICE => 10,
            ProductInterface::VISIBILITY => Visibility::VISIBILITY_BOTH,
            ProductInterface::STATUS => $productData['status'],
            'website_ids' => [$defaultWebsiteId],
            'stock_data' => [
                'use_config_manage_stock' => 1,
                'qty' => 100,
                'is_qty_decimal' => 0,
                'is_in_stock' => 1,
            ],
            'category_ids' => [$defaultCategory->getId()],
            'tax_class_id' => 2, //Taxable Goods
        ];
        $product->setData($productData);

        return $productRepository->save($product);
    }
}
