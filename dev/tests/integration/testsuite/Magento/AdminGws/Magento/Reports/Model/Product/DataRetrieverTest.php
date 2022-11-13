<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Magento\Reports\Model\Product;

use Magento\AdminGws\Model\Role as AdminGwsRole;
use Magento\Authorization\Model\Role as AuthRole;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Observer\SwitchPriceAttributeScopeOnConfigChange;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Reports\Model\Product\DataRetriever;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 */
class DataRetrieverTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var DataRetriever
     */
    private $dataRetriever;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();

        /** @var ReinitableConfigInterface $reinitiableConfig */
        $reinitiableConfig = $this->objectManager->get(ReinitableConfigInterface::class);
        $reinitiableConfig->setValue(
            'catalog/price/scope',
            Store::PRICE_SCOPE_WEBSITE
        );
        $observer = $this->objectManager->get(Observer::class);
        $this->objectManager->get(SwitchPriceAttributeScopeOnConfigChange::class)
            ->execute($observer);

        $this->storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $this->dataRetriever = $this->objectManager->create(DataRetriever::class);
    }

    /**
     * Test retrieve products data for reports by entity id's
     *
     * @magentoDataFixture Magento/AdminGws/_files/quote_item_of_product_on_different_websites.php
     * @magentoConfigFixture default/reports/options/enabled 1
     * @magentoConfigFixture current_store catalog/price/scope 1
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     *
     * @param string $adminName
     * @param int $expectedPrice
     * @dataProvider prepareActiveCartItemsDataProvider
     * @return void
     */
    public function testExecute(string $adminName, int $expectedPrice): void
    {
        /** @var AuthRole $adminRole */
        $adminRole = $this->objectManager->get(AuthRole::class);
        $adminRole->load($adminName, 'role_name');

        /** @var AdminGwsRole $adminGwsRole */
        $adminGwsRole = $this->objectManager->get(AdminGwsRole::class);
        $adminGwsRole->setAdminRole($adminRole);

        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $product = $productRepository->get('simple_report');
        $productId = $product->getId();

        $actualResult = $this->dataRetriever->execute([$productId]);
        $this->assertNotEmpty($actualResult);
        $this->assertCount(1, $actualResult);
        $this->assertEquals($expectedPrice, $actualResult[$productId]['price']);

        // restore admin role for proper rollback access
        $adminRole->load('role_has_general_access', 'role_name');
        $adminGwsRole->setAdminRole($adminRole);
    }

    /**
     * Provider for testExecute
     *
     * @return array
     */
    public function prepareActiveCartItemsDataProvider() : array
    {
        return [
            'restricted role' => ['role_has_test_website_access_only', 123],
            'unrestricted role' => ['role_has_general_access', 321],
        ];
    }
}
