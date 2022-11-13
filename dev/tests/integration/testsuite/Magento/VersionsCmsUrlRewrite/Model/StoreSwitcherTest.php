<?php
/***
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCmsUrlRewrite\Model;

use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreSwitcher\CannotSwitchStoreException;
use Magento\Store\Model\StoreSwitcherInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\VersionsCms\Api\HierarchyNodeRepositoryInterface;
use PHPUnit\Framework\TestCase;

/**
 * VersionsCMS Store Switcher test.
 */
class StoreSwitcherTest extends TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var StoreSwitcherInterface
     */
    private $storeSwitcher;

    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;

    /**
     * @var HierarchyNodeRepositoryInterface
     */
    private $nodeRepository;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->storeSwitcher = $this->objectManager->get(StoreSwitcherInterface::class);
        $this->storeRepository = $this->objectManager->get(StoreRepositoryInterface::class);
        $this->nodeRepository = $this->objectManager->get(HierarchyNodeRepositoryInterface::class);
    }

    /**
     * Tests case when pages and hierarchy are only for one store.
     *
     * In this case after store switching on the store where pages are absent home page should open.
     *
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Store/_files/store.php
     * @magentoDataFixture Magento/VersionsCmsUrlRewrite/_files/hierarchy_nodes_with_pages_on_default_store_view_only.php
     *
     * @dataProvider storeSwitchingDataProvider
     *
     * @param string $fromStoreKey
     * @param string $toStoreKey
     * @param string $sourceUrl
     * @param string $expectedUrl
     * @param bool $storeCodeInUrl
     *
     * @throws CannotSwitchStoreException
     * @throws NoSuchEntityException
     */
    public function testStoreSwitchingWithoutPagesInSecondStore(
        string $fromStoreKey,
        string $toStoreKey,
        string $sourceUrl,
        string $expectedUrl,
        bool $storeCodeInUrl
    ): void {
        $fromStore = $this->storeRepository->get($fromStoreKey);
        $targetStore = $this->storeRepository->get($toStoreKey);

        $this->objectManager->get(ReinitableConfigInterface::class)
            ->setValue(Store::XML_PATH_STORE_IN_URL, $storeCodeInUrl, ScopeInterface::SCOPE_STORE, $fromStoreKey);
        $this->objectManager->get(ReinitableConfigInterface::class)
            ->setValue(Store::XML_PATH_STORE_IN_URL, $storeCodeInUrl, ScopeInterface::SCOPE_STORE, $toStoreKey);

        $result = $this->storeSwitcher->switch($fromStore, $targetStore, $sourceUrl);
        self::assertEquals($expectedUrl, $result);
    }

    public function storeSwitchingDataProvider()
    {
        return [
            [
                'default',
                'test',
                'http://localhost/page-1/page-2',
                'http://localhost/index.php/',
                false
            ],
            [
                'default',
                'test',
                'http://localhost/test/page-1/page-2',
                'http://localhost/index.php/test/',
                true
            ]
        ];
    }

    /**
     * Test store switch when cms pages assigned to two different store views and two hierarchy with the same url
     *
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/VersionsCmsUrlRewrite/_files/hierarchy_nodes_with_pages_on_two_store_views.php
     *
     * @dataProvider storeSwitchingWithPagesDataProvider
     *
     * @param string $fromStoreKey
     * @param string $toStoreKey
     * @param string $sourceUrl
     * @param string $expectedUrl
     *
     * @throws CannotSwitchStoreException
     * @throws NoSuchEntityException
     */
    public function testStoreSwitchingWithPagesInSecondStore(
        string $fromStoreKey,
        string $toStoreKey,
        string $sourceUrl,
        string $expectedUrl
    ): void {
        $fromStore = $this->storeRepository->get($fromStoreKey);
        $targetStore = $this->storeRepository->get($toStoreKey);
        $result = $this->storeSwitcher->switch($fromStore, $targetStore, $sourceUrl);
        self::assertEquals($expectedUrl, $result);
    }

    public function storeSwitchingWithPagesDataProvider(): array
    {
        return [
            [
                'default',
                'test',
                'http://localhost/page-1/page-2',
                'http://localhost/page-1/page-2',
            ]
        ];
    }
}
