<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Model;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogRule\Model\ResourceModel\Rule\Collection as CatalogRuleCollection;
use Magento\CatalogRuleStaging\Api\CatalogRuleStagingInterface;
use Magento\CatalogStaging\Api\CategoryStagingInterface;
use Magento\CatalogStaging\Api\ProductStagingInterface;
use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\CmsStaging\Api\BlockStagingInterface;
use Magento\CmsStaging\Api\PageStagingInterface;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\EntityManager\TypeResolver;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\SalesRuleStaging\Api\SalesRuleStagingInterface;
use Magento\Staging\Api\Data\UpdateInterfaceFactory;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\ResourceModel\Db\ReadEntityVersion;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SynchronizeEntityPeriodTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var UpdateRepositoryInterface
     */
    private $updateRepository;

    /**
     * @var VersionManager
     */
    private $versionManager;

    /**
     * @var ReadEntityVersion
     */
    private $entityVersionReader;

    /**
     * @var TypeResolver
     */
    private $typeResolver;

    /**
     * @var SynchronizeEntityPeriod
     */
    private $synchronizer;

    /**
     * @var \Magento\Staging\Api\Data\UpdateInterface[]
     */
    private $updates;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

        $this->updateRepository = $this->objectManager->get(UpdateRepositoryInterface::class);
        $this->versionManager = $this->objectManager->get(VersionManager::class);
        $this->entityVersionReader = $this->objectManager->get(ReadEntityVersion::class);
        $this->typeResolver = $this->objectManager->get(TypeResolver::class);
        $this->synchronizer = $this->objectManager->create(SynchronizeEntityPeriod::class);

        $updateFactory = $this->objectManager->get(UpdateInterfaceFactory::class);
        $this->updates = [];
        $versions = [
            \strtotime('+1 day'),
            \strtotime('+2 day'),
        ];
        \rsort($versions);
        $movedTo = null;
        foreach ($versions as $version) {
            $update = $updateFactory->create();
            $update->setName('Update '.$version);
            $update->setStartTime(date(\DATE_ATOM, $version));
            $update->setMovedTo($movedTo);
            $this->updateRepository->save($update);
            $this->updates[$update->getId()] = $update;
            $movedTo = $update->getId();
        }
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        foreach ($this->updates as $update) {
            $this->updateRepository->delete($update);
        }
    }

    /**
     * @magentoDataFixture Magento/Cms/_files/pages.php
     */
    public function testExecuteCmsPage()
    {
        $pageIdentifier = 'page100';

        $filter = $this->objectManager->create(Filter::class);
        $filter->setField('identifier')->setValue($pageIdentifier);
        $filterGroup = $this->objectManager->create(FilterGroup::class);
        $filterGroup->setFilters([$filter]);
        $searchCriteria = $this->objectManager->create(SearchCriteriaInterface::class);
        $searchCriteria->setFilterGroups([$filterGroup]);
        $pageRepository = $this->objectManager->get(PageRepositoryInterface::class);
        $pageSearchResults = $pageRepository->getList($searchCriteria);
        $pages = $pageSearchResults->getItems();
        /** @var \Magento\Cms\Api\Data\PageInterface $page */
        $page = \array_values($pages)[0];

        $pageStaging = $this->objectManager->get(PageStagingInterface::class);
        $this->executeSynchronize($pageStaging, $page, $page->getId());
    }

    /**
     * @magentoDataFixture Magento/Cms/_files/block.php
     */
    public function testExecuteCmsBlock()
    {
        $blockIdentifier = 'fixture_block';

        $filter = $this->objectManager->create(Filter::class);
        $filter->setField('identifier')->setValue($blockIdentifier);
        $filterGroup = $this->objectManager->create(FilterGroup::class);
        $filterGroup->setFilters([$filter]);
        $searchCriteria = $this->objectManager->create(SearchCriteriaInterface::class);
        $searchCriteria->setFilterGroups([$filterGroup]);
        $blockRepository = $this->objectManager->get(BlockRepositoryInterface::class);
        $blockSearchResults = $blockRepository->getList($searchCriteria);
        $blocks = $blockSearchResults->getItems();
        /** @var \Magento\Cms\Api\Data\BlockInterface $block */
        $block = \array_values($blocks)[0];

        $blockStaging = $this->objectManager->get(BlockStagingInterface::class);
        $this->executeSynchronize($blockStaging, $block, $block->getId());
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/second_product_simple.php
     */
    public function testExecuteProduct()
    {
        $productSku = 'simple2';

        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $product = $productRepository->get($productSku);

        $productStaging = $this->objectManager->get(ProductStagingInterface::class);
        $this->executeSynchronize($productStaging, $product, $product->getId());
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/category.php
     */
    public function testExecuteCatalogCategory()
    {
        $categoryId = 333;

        $categoryRepository = $this->objectManager->get(CategoryRepositoryInterface::class);
        $category = $categoryRepository->get($categoryId);

        $categoryStaging = $this->objectManager->get(CategoryStagingInterface::class);
        $this->executeSynchronize($categoryStaging, $category, $category->getId());
    }

    /**
     * @magentoDataFixture Magento/SalesRule/_files/rule_specific_date.php
     */
    public function testExecuteSalesRule()
    {
        $salesRuleName = '#1';

        $filterGroup = $this->objectManager->create(FilterGroup::class);
        $filterGroup->setData('name', $salesRuleName);
        $searchCriteria = $this->objectManager->create(SearchCriteriaInterface::class);
        $searchCriteria->setFilterGroups([$filterGroup]);
        $salesRuleRepository = $this->objectManager->get(RuleRepositoryInterface::class);
        $salesRuleSearchResult = $salesRuleRepository->getList($searchCriteria);
        $salesRules = $salesRuleSearchResult->getItems();
        /** @var \Magento\SalesRule\Api\Data\RuleInterface $salesRule */
        $salesRule = \array_values($salesRules)[0];

        $salesRuleStaging = $this->objectManager->get(SalesRuleStagingInterface::class);
        $this->executeSynchronize($salesRuleStaging, $salesRule, $salesRule->getRuleId());
    }

    /**
     * @magentoDataFixture Magento/CatalogRule/_files/rule_by_category_ids.php
     */
    public function testExecuteCatalogRule()
    {
        $catalogRuleName = 'test_category_rule';

        $catalogRuleCollection = $this->objectManager->create(CatalogRuleCollection::class);
        $catalogRuleCollection->addFilter('name', $catalogRuleName);
        $catalogRuleCollection->load();
        /** @var \Magento\CatalogRule\Model\Rule $catalogRule */
        $catalogRule = $catalogRuleCollection->getFirstItem();

        $catalogRuleStaging = $this->objectManager->get(CatalogRuleStagingInterface::class);
        $this->executeSynchronize($catalogRuleStaging, $catalogRule, $catalogRule->getId());
    }

    /**
     * @param object $entityStaging
     * @param object $entity
     * @param string $entityIdentifier
     */
    private function executeSynchronize($entityStaging, $entity, $entityIdentifier)
    {
        $versions = \array_keys($this->updates);
        $entityStaging->schedule($entity, (string) \min($versions));
        $this->synchronizer->execute();

        $this->versionManager->setCurrentVersionId(\max($versions));
        $entityType = $this->typeResolver->resolve($entity);
        $entityId = $this->entityVersionReader->getCurrentVersionRowId($entityType, $entityIdentifier);
        $this->assertNotEmpty($entityId, "{$entityType} staging synchronization failed.");
    }
}
