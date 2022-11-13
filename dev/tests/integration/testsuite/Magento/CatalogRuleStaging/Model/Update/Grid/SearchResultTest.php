<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogRuleStaging\Model\Update\Grid;

use Magento\Framework\Api\Search\DocumentInterface;
use Magento\Staging\Model\Update\Cleaner;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Magento\CatalogRule\Api\CatalogRuleRepositoryInterface;
use Magento\CatalogRule\Model\ResourceModel\Rule\CollectionFactory;
use Magento\Staging\Model\Update\Grid\SearchResult;

/**
 * Tests \Magento\Staging\Model\Update\Grid\SearchResult
 *
 * This test is for the Magento_Staging module
 * @see \Magento\Staging\Model\Update\Grid\SearchResult
 * This particular flow can be covered only with a help of a staging entity like CatalogRule
 * That is why this test is in the Magento_CatalogRuleStaging module but not in the Magento_Staging module
 */
class SearchResultTest extends TestCase
{
    /**
     * Checks whether items on dashboard are displayed with objects (includes)
     *
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/CatalogRuleStaging/_files/two_catalog_rules_with_updates.php
     */
    public function testUpdatesOnStagingDashboardAreDisplayedOnlyWithObjects()
    {
        $this->assertCount(2, $this->getSearchResultItems());
        $this->deleteRule();
        Bootstrap::getObjectManager()->create(Cleaner::class)->execute();
        $this->assertCount(1, $this->getSearchResultItems());
    }

    /**
     * Deletes a rule
     *
     * @return void
     */
    private function deleteRule()
    {
        $collectionFactory = Bootstrap::getObjectManager()->create(CollectionFactory::class);
        $collection = $collectionFactory->create();
        $catalogRule = $collection->getFirstItem();
        $catalogRuleRepository = Bootstrap::getObjectManager()->create(CatalogRuleRepositoryInterface::class);
        $catalogRuleRepository->delete($catalogRule);
    }

    /**
     * Retrieve search result items
     *
     * @return DocumentInterface[]
     */
    private function getSearchResultItems()
    {
        $searchResult = Bootstrap::getObjectManager()->create(SearchResult::class);
        return $searchResult->getItems();
    }
}
