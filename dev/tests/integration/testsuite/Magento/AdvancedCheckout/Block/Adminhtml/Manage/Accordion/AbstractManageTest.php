<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Block\Adminhtml\Manage\Accordion;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Data\Collection;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Class consist of base logic for grids testing on manage shopping cart page
 */
abstract class AbstractManageTest extends TestCase
{
    /** @var ObjectManagerInterface */
    protected $objectManager;

    /** @var LayoutInterface */
    protected $layout;

    /** @var BlockInterface */
    protected $block;

    /** @var Registry */
    private $registry;

    /** @var CustomerRepositoryInterface */
    private $customerRepository;

    /** @var StoreManagerInterface */
    protected $storeManager;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->layout = $this->objectManager->get(LayoutInterface::class);
        $this->registry = $this->objectManager->get(Registry::class);
        $this->customerRepository = $this->objectManager->get(CustomerRepositoryInterface::class);
        $this->storeManager = $this->objectManager->get(StoreManagerInterface::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        $this->registry->unregister('checkout_current_customer');
        $this->registry->unregister('checkout_current_store');

        parent::tearDown();
    }

    /**
     * Prepare registry
     *
     * @param string $customerEmail
     * @param string $storeCode
     * @return void
     */
    protected function prepareRegistry(string $customerEmail, string $storeCode): void
    {
        $store = $this->storeManager->getStore($storeCode);
        $websiteId = $store->getWebsiteId();
        $customer = $this->customerRepository->get($customerEmail, $websiteId);
        $this->registry->register('checkout_current_customer', $customer);
        $this->registry->register('checkout_current_store', $store);
    }

    /**
     * Check item collection
     *
     * @param array $productSkus
     * @param Collection $collection
     * @return void
     */
    protected function assertCollectionItem(array $productSkus, Collection $collection): void
    {
        $this->assertCount(count($productSkus), $collection);
        foreach ($productSkus as $productSku) {
            $this->assertNotNull($collection->getItemByColumnValue(ProductInterface::SKU, $productSku));
        }
    }
}
