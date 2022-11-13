<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Staging\Model\Update\Grid;

use Magento\Framework\Intl\DateTimeFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\Staging\Model\Update\Source\Status;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for \Magento\Staging\Model\Update\Grid\SearchResult class.
 */
class SearchResultTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var SearchResult
     */
    private $searchResult;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->searchResult = $this->objectManager->create(SearchResult::class);
    }

    /**
     * @return void
     */
    public function testAddOldUpdatesFilter(): void
    {
        $dateTimeFactoryMock = $this->getMockBuilder(DateTimeFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dateTimeObject = new \DateTime('2001-12-31 05:00:11');
        $time = $dateTimeObject->format('Y-m-d H:i:s');
        $dateTimeFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($dateTimeObject);

        /** @var SearchResult $searchResult */
        $searchResult = $this->objectManager->create(
            SearchResult::class,
            ['dateTimeFactory' => $dateTimeFactoryMock]
        );
        $this->assertStringContainsString(
            "(rollbacks.start_time >= '{$time}') OR (rollbacks.start_time IS NULL)",
            $searchResult->getSelect()->assemble()
        );
    }

    /**
     * @magentoDataFixture Magento/Staging/_files/staging_entity_two_campaigns.php
     * @magentoDataFixture Magento/Staging/_files/search_staging_update.php
     * @return void
     */
    public function testStatusColumn(): void
    {
        $this->searchResult->setOrder('status', SearchResult::SORT_ORDER_DESC);
        $items = array_values($this->searchResult->getItems());
        $this->assertCount(2, $items);
        $this->assertEquals(100, $items[0]['id']);
        $this->assertEquals(Status::STATUS_UPCOMING, $items[0]['status']);
        $this->assertEquals(1, $items[1]['id']);
        $this->assertEquals(Status::STATUS_ACTIVE, $items[1]['status']);
    }

    /**
     * Tests the total count of the search result with an update without includes.
     *
     * @magentoDataFixture Magento/Staging/_files/search_staging_update.php
     * @return void
     */
    public function testRecordsCounter(): void
    {
        $this->assertCount(1, $this->searchResult->getItems());
        $this->assertCount(2, $this->searchResult->getData());
        $this->assertEquals(1, $this->searchResult->getTotalCount());
    }
}
