<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Banner\Model\ResourceModel;

use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\SalesRule\Api\Data\RuleInterface;
use Magento\Banner\Model\BannerFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Framework\ObjectManagerInterface;
use Magento\Customer\Model\GroupManagement;
use Magento\Framework\Registry;
use PHPUnit\Framework\TestCase;

class BannerTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var Banner
     */
    private $resourceModel;

    /**
     * @var int
     */
    private $websiteId = 1;

    /**
     * @var BannerFactory
     */
    private $bannerFactory;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var RuleRepositoryInterface
     */
    private $ruleRepository;

    /**
     * @var int
     */
    private $customerGroupId = GroupManagement::NOT_LOGGED_IN_ID;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->resourceModel = $this->objectManager->get(Banner::class);
        $this->bannerFactory = $this->objectManager->get(BannerFactory::class);
        $this->registry = $this->objectManager->get(Registry::class);
        $this->ruleRepository = $this->objectManager->get(RuleRepositoryInterface::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        $this->resourceModel = null;
    }

    /**
     * @return void
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoDataFixture Magento/CatalogRule/_files/catalog_rule_10_off_not_logged.php
     * @magentoDataFixture Magento/Banner/_files/banner.php
     * @magentoDbIsolation disabled
     */
    public function testGetCatalogRuleRelatedBannerIdsNoBannerConnected(): void
    {
        $this->assertEmpty(
            $this->resourceModel->getCatalogRuleRelatedBannerIds($this->websiteId, $this->customerGroupId)
        );
    }

    /**
     * @return void
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoDataFixture Magento/Banner/_files/banner_catalog_rule.php
     * @magentoDbIsolation disabled
     */
    public function testGetCatalogRuleRelatedBannerIds(): void
    {
        $banner = $this->bannerFactory->create();
        $this->resourceModel->load($banner, 'Test Dynamic Block', 'name');

        $this->assertSame(
            [$banner->getId()],
            $this->resourceModel->getCatalogRuleRelatedBannerIds($this->websiteId, $this->customerGroupId)
        );
    }

    /**
     * @return void
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoDataFixture Magento/Banner/_files/banner_catalog_rule.php
     * @dataProvider getCatalogRuleRelatedBannerIdsWrongDataDataProvider
     * @magentoDbIsolation disabled
     */
    public function testGetCatalogRuleRelatedBannerIdsWrongData($websiteId, $customerGroupId): void
    {
        $this->assertEmpty($this->resourceModel->getCatalogRuleRelatedBannerIds($websiteId, $customerGroupId));
    }

    /**
     * @return array
     */
    public function getCatalogRuleRelatedBannerIdsWrongDataDataProvider(): array
    {
        return [
            'wrong website' => [$this->websiteId + 1, $this->customerGroupId],
            'wrong customer group' => [$this->websiteId, $this->customerGroupId + 1]
        ];
    }

    /**
     * @return void
     * @magentoDataFixture Magento/Banner/_files/banner_disabled_40_percent_off.php
     * @magentoDataFixture Magento/Banner/_files/banner_enabled_40_to_50_percent_off.php
     * @magentoDbIsolation disabled
     */
    public function testGetSalesRuleRelatedBannerIds(): void
    {
        $ruleId = $this->registry->registry('Magento/SalesRule/_files/cart_rule_40_percent_off');
        /** @var \Magento\Banner\Model\Banner $banner */
        $banner = $this->bannerFactory->create();
        $this->resourceModel->load($banner, 'Get from 40% to 50% Off on Large Orders', 'name');

        $this->assertEquals(
            [$banner->getId()],
            $this->resourceModel->getSalesRuleRelatedBannerIds([$ruleId])
        );
    }

    /**
     * Get sales rule related banner ids with non active sales rule
     *
     * @return void
     * @magentoDataFixture Magento/Banner/_files/banner_enabled_40_to_50_percent_off.php
     * @magentoDbIsolation disabled
     */
    public function testGetSalesRuleRelatedBannerIdsWithNonActiveRule(): void
    {
        $ruleId = $this->registry->registry('Magento/SalesRule/_files/cart_rule_40_percent_off');
        /** @var RuleInterface $rule */
        $rule = $this->ruleRepository->getById($ruleId);
        $rule->setIsActive(0);
        $this->ruleRepository->save($rule);

        $this->assertEmpty($this->resourceModel->getSalesRuleRelatedBannerIds([$ruleId]));
    }

    /**
     * @return void
     * @magentoDataFixture Magento/Banner/_files/banner_enabled_40_to_50_percent_off.php
     * @magentoDataFixture Magento/Banner/_files/banner_disabled_40_percent_off.php
     * @magentoDbIsolation disabled
     */
    public function testGetSalesRuleRelatedBannerIdsNoRules(): void
    {
        $this->assertEmpty($this->resourceModel->getSalesRuleRelatedBannerIds([]));
    }
}
