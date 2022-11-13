<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Banner;

use Magento\Banner\Model\Banner;
use Magento\Banner\Model\ResourceModel\Banner\CollectionFactory;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\GraphQl\GetCustomerAuthenticationHeader;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test for dynamic blocks query
 *
 * @magentoAppIsolation enabled
 */
class DynamicBlocksQueryTest extends GraphQlAbstract
{
    /**
     * @var Uid
     */
    private $idEncoder;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var GetCustomerAuthenticationHeader
     */
    private $getCustomerAuthenticationHeader;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->idEncoder = $objectManager->get(Uid::class);
        $this->collectionFactory = $objectManager->get(CollectionFactory::class);
        $this->getCustomerAuthenticationHeader = $objectManager->get(GetCustomerAuthenticationHeader::class);
    }

    /**
     * Test for specific dynamic blocks query without locations field by visitor
     *
     * @magentoApiDataFixture Magento/Banner/_files/banner.php
     */
    public function testSpecificDynamicBlocksQueryWithoutLocations(): void
    {
        $banner = $this->getBannerByName('Test Dynamic Block');
        $response = $this->executeQuery('SPECIFIED', null, [$banner->getId()]);
        self::assertEquals($this->idEncoder->encode($banner->getId()), $response['dynamicBlocks']['items'][0]['uid']);
        self::assertEquals(
            'Dynamic Block Content',
            $response['dynamicBlocks']['items'][0]['content']['html']
        );
        self::assertEquals(1, $response['dynamicBlocks']['page_info']['current_page']);
        self::assertEquals(20, $response['dynamicBlocks']['page_info']['page_size']);
        self::assertEquals(1, $response['dynamicBlocks']['page_info']['total_pages']);
        self::assertEquals(1, $response['dynamicBlocks']['total_count']);
    }

    /**
     * Test for specific dynamic blocks query field by customer
     *
     * @magentoApiDataFixture Magento/Banner/_files/banner_with_location_and_customer_segment.php
     */
    public function testDynamicBlocksQueryWithLocationsByCustomer(): void
    {
        $banner = $this->getBannerByName('Test Dynamic Block with location and segment');
        $response = $this->executeQuery(
            'SPECIFIED',
            ['HEADER', 'FOOTER'],
            [$banner->getId()],
            'customer@search.example.com',
            'password'
        );
        self::assertEquals($this->idEncoder->encode($banner->getId()), $response['dynamicBlocks']['items'][0]['uid']);
        self::assertEquals(
            '<p>Dynamic Block Content with location and segment</p>',
            $response['dynamicBlocks']['items'][0]['content']['html']
        );
        self::assertEquals(1, $response['dynamicBlocks']['page_info']['current_page']);
        self::assertEquals(20, $response['dynamicBlocks']['page_info']['page_size']);
        self::assertEquals(1, $response['dynamicBlocks']['page_info']['total_pages']);
        self::assertEquals(1, $response['dynamicBlocks']['total_count']);
    }

    /**
     * Test for specific dynamic blocks which are not allowed to customer
     *
     * @magentoApiDataFixture Magento/Banner/_files/banner_with_location_and_customer_segment.php
     */
    public function testDynamicBlocksNotAllowedToCustomer(): void
    {
        $banner = $this->getBannerByName('Test Dynamic Block with location and segment');
        $response = $this->executeQuery(
            'SPECIFIED',
            ['HEADER', 'FOOTER'],
            [$banner->getId()],
            'customer_with_addresses@test.com',
            'password'
        );

        self::assertEmpty($response['dynamicBlocks']['items']);
        self::assertEquals(1, $response['dynamicBlocks']['page_info']['current_page']);
        self::assertEquals(20, $response['dynamicBlocks']['page_info']['page_size']);
        self::assertEquals(0, $response['dynamicBlocks']['page_info']['total_pages']);
        self::assertEquals(0, $response['dynamicBlocks']['total_count']);
    }

    /**
     * Test for specific dynamic blocks which are not allowed to visitor
     *
     * @magentoApiDataFixture Magento/Banner/_files/banner_with_location_and_customer_segment.php
     */
    public function testDynamicBlocksWithSegmentsByVisitor(): void
    {
        $banner = $this->getBannerByName('Test Dynamic Block with location and segment');
        $response = $this->executeQuery(
            'SPECIFIED',
            ['HEADER', 'FOOTER'],
            [$banner->getId()]
        );

        self::assertEmpty($response['dynamicBlocks']['items']);
        self::assertEquals(1, $response['dynamicBlocks']['page_info']['current_page']);
        self::assertEquals(20, $response['dynamicBlocks']['page_info']['page_size']);
        self::assertEquals(0, $response['dynamicBlocks']['page_info']['total_pages']);
        self::assertEquals(0, $response['dynamicBlocks']['total_count']);
    }

    /**
     * Test for cart price rule related dynamic blocks query
     *
     * @magentoApiDataFixture Magento/Banner/_files/banner_enabled_40_to_50_percent_off.php
     */
    public function testCartPriceRuleRelatedDynamicBlocksQuery(): void
    {
        $response = $this->executeQuery('CART_PRICE_RULE_RELATED');

        self::assertNotEmpty($response['dynamicBlocks']['items']);
        self::assertIsNumeric($this->idEncoder->decode($response['dynamicBlocks']['items'][0]['uid']));
        self::assertEquals(
            '<img src="http://example.com/banner_40_to_50_percent_off.png" />',
            $response['dynamicBlocks']['items'][0]['content']['html']
        );
        self::assertEquals(1, $response['dynamicBlocks']['page_info']['current_page']);
        self::assertEquals(20, $response['dynamicBlocks']['page_info']['page_size']);
        self::assertEquals(1, $response['dynamicBlocks']['page_info']['total_pages']);
        self::assertEquals(1, $response['dynamicBlocks']['total_count']);
    }

    /**
     * Test for catalog price rule related dynamic blocks query
     *
     * @magentoApiDataFixture Magento/Banner/_files/banner_catalog_rule.php
     */
    public function testCatalogPriceRuleRelatedDynamicBlocksQuery(): void
    {
        $response = $this->executeQuery('CATALOG_PRICE_RULE_RELATED');

        self::assertNotEmpty($response['dynamicBlocks']['items']);
        self::assertIsNumeric($this->idEncoder->decode($response['dynamicBlocks']['items'][0]['uid']));
        self::assertEquals(
            'Dynamic Block Content',
            $response['dynamicBlocks']['items'][0]['content']['html']
        );
        self::assertEquals(1, $response['dynamicBlocks']['page_info']['current_page']);
        self::assertEquals(20, $response['dynamicBlocks']['page_info']['page_size']);
        self::assertEquals(1, $response['dynamicBlocks']['page_info']['total_pages']);
        self::assertEquals(1, $response['dynamicBlocks']['total_count']);
    }

    /**
     * Test dynamic blocks query with not existing banner ids
     *
     * @magentoApiDataFixture Magento/Banner/_files/banner.php
     */
    public function testDynamicBlocksQueryWithNotExistingIds():void
    {
        $banner = $this->getBannerByName('Test Dynamic Block');
        $response = $this->executeQuery('SPECIFIED', null, [$banner->getId()+1000]);
        self::assertEmpty($response['dynamicBlocks']['items']);
        self::assertEquals(1, $response['dynamicBlocks']['page_info']['current_page']);
        self::assertEquals(20, $response['dynamicBlocks']['page_info']['page_size']);
        self::assertEquals(0, $response['dynamicBlocks']['page_info']['total_pages']);
        self::assertEquals(0, $response['dynamicBlocks']['total_count']);
    }

    /**
     * Test dynamic blocks query with not encoded banner ids
     *
     * @magentoApiDataFixture Magento/Banner/_files/banner.php
     */
    public function testDynamicBlocksQueryWithNotEncodedIds(): void
    {
        $bannerId = $this->getBannerByName('Test Dynamic Block')->getId();
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Value of uid \"{$bannerId}\" is incorrect.");

        $query = <<<QUERY
{
  dynamicBlocks(input: {
    type: SPECIFIED
    dynamic_block_uids: ["{$bannerId}"]
  }) {
      items {
        uid
        content {
          html
        }
      }
      page_info {
        current_page
        page_size
        total_pages
      }
      total_count
  }
}
QUERY;

        $this->graphQlQuery($query);
    }

    /**
     * Test dynamic blocks query with disabled banners
     *
     * @magentoApiDataFixture Magento/Banner/_files/banner_disabled_40_percent_off.php
     */
    public function testDisabledDynamicBlocksQuery(): void
    {
        $response = $this->executeQuery('CART_PRICE_RULE_RELATED');
        self::assertEmpty($response['dynamicBlocks']['items']);
        self::assertEquals(1, $response['dynamicBlocks']['page_info']['current_page']);
        self::assertEquals(20, $response['dynamicBlocks']['page_info']['page_size']);
        self::assertEquals(0, $response['dynamicBlocks']['page_info']['total_pages']);
        self::assertEquals(0, $response['dynamicBlocks']['total_count']);
    }

    /**
     * Test dynamic blocks query with wrong page size amount
     *
     * @magentoApiDataFixture Magento/Banner/_files/banner.php
     */
    public function testDynamicBlocksQueryWithWrongPageSize(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('pageSize value must be greater than 0.');
        $banner = $this->getBannerByName('Test Dynamic Block');
        $this->executeQuery(
            'SPECIFIED',
            null,
            [$banner->getId()],
            null,
            null,
            0,
            1
        );
    }

    /**
     * Test dynamic blocks query with wrong current page amount
     *
     * @magentoApiDataFixture Magento/Banner/_files/banner.php
     */
    public function testDynamicBlocksQueryWithWrongCurrentPage(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('currentPage value must be greater than 0.');
        $banner = $this->getBannerByName('Test Dynamic Block');
        $this->executeQuery(
            'SPECIFIED',
            null,
            [$banner->getId()],
            null,
            null,
            10,
            -1
        );
    }

    /**
     * Execute query for dynamic blocks
     *
     * @param string $type
     * @param array $locations
     * @param array $ids
     * @param string|null $email
     * @param string|null $password
     * @param int|null $pageSize
     * @param int|null $currentPage
     * @return array|bool|float|int|string
     * @throws AuthenticationException
     */
    private function executeQuery(
        string $type,
        ?array $locations = null,
        ?array $ids = null,
        ?string $email = null,
        ?string $password = null,
        ?int $pageSize = null,
        ?int $currentPage = null
    ) {
        $dynamicBlockUids = $this->prepareDynamicBlockUids($ids);
        $locations = $this->prepareLocations($locations);
        $pageSize = $this->preparePageSize($pageSize);
        $currentPage = $this->prepareCurrentPage($currentPage);

        $query = <<<QUERY
{
  dynamicBlocks(input: {
    type: {$type}
    {$locations}
    {$dynamicBlockUids}
  }
  {$pageSize}
  {$currentPage}
  ) {
      items {
        uid
        content {
          html
        }
      }
      page_info {
        current_page
        page_size
        total_pages
      }
      total_count
  }
}
QUERY;

        if ($email && $password) {
            return $this->graphQlQuery(
                $query,
                [],
                '',
                $this->getCustomerAuthenticationHeader->execute($email, $password)
            );
        }

        return $this->graphQlQuery($query);
    }

    /**
     * Prepare uids input parameter
     *
     * @param array $ids
     * @return string
     */
    private function prepareDynamicBlockUids(?array $ids): string
    {
        if ($ids) {
            $uids = [];
            foreach ($ids as $id) {
                $uids[] = '"' . $this->idEncoder->encode((string)$id) . '"';
            }

            if (!empty($uids)) {
                $uids = implode(', ', $uids);
                return "dynamic_block_uids: [{$uids}]";
            }
        }

        return '';
    }

    /**
     * Prepare locations input parameter
     *
     * @param array|null $locations
     * @return string
     */
    private function prepareLocations(?array $locations): string
    {
        if ($locations!==null) {
            $locations = implode(', ', $locations);
            return "locations: [{$locations}]";
        }
        return '';
    }

    /**
     * Get banner by name
     *
     * @param string $bannerName
     * @return Banner
     */
    private function getBannerByName(string $bannerName): Banner
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('name', $bannerName);
        return $collection->getFirstItem();
    }

    /**
     * Prepare pageSize field
     *
     * @param int|null $pageSize
     * @return string
     */
    private function preparePageSize(?int $pageSize): string
    {
        if ($pageSize!==null) {
            return 'pageSize: ' . $pageSize;
        }

        return '';
    }

    /**
     * Prepare currentPage field
     *
     * @param int|null $currentPage
     * @return string
     */
    private function prepareCurrentPage(?int $currentPage): string
    {
        if ($currentPage!==null) {
            return 'currentPage: ' . $currentPage;
        }

        return '';
    }
}
