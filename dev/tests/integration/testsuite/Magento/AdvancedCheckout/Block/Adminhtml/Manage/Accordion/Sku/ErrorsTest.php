<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Block\Adminhtml\Manage\Accordion\Sku;

use Magento\AdvancedCheckout\Helper\Data;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Checks errors block appearance on manage shopping cart page
 *
 * @see \Magento\AdvancedCheckout\Block\Adminhtml\Manage\Accordion\Sku\Errors
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class ErrorsTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var Errors */
    private $block;

    /** @var DataObjectFactory */
    private $dataObjectFactory;

    /** @var Session */
    private $customerSession;

    /** @var Data */
    private $customerHelper;

    /** @var Registry */
    private $registry;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var CustomerRepositoryInterface */
    private $customerRepository;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->block = $this->objectManager->get(LayoutInterface::class)->createBlock(Errors::class);
        $this->dataObjectFactory = $this->objectManager->get(DataObjectFactory::class);
        $this->customerSession = $this->objectManager->get(Session::class);
        $this->customerHelper = $this->objectManager->get(Data::class);
        $this->registry = $this->objectManager->get(Registry::class);
        $this->storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $this->customerRepository = $this->objectManager->get(CustomerRepositoryInterface::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        $this->customerSession->logout();
        $this->registry->unregister('checkout_current_customer');
        $this->registry->unregister('checkout_current_store');

        parent::tearDown();
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     *
     * @return void
     */
    public function testGetHeaderText(): void
    {
        $this->prepareCustomerSession(1);
        $this->prepareFailedItems(2);
        $this->assertEquals(
            __('<span><span id="sku-attention-num">%1</span> product(s) require attention.</span>', 2),
            (string)$this->block->getHeaderText()
        );
    }

    /**
     * @return void
     */
    public function testGetButtonsHtml(): void
    {
        $result = $this->block->getButtonsHtml();
        $this->assertStringContainsString('addBySku.removeAllFailed()', $result);
        $this->assertStringContainsString('Remove All', strip_tags($result));
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     *
     * @return void
     */
    public function testEmptyHtml(): void
    {
        $this->prepareCustomerSession(1);
        $this->prepareFailedItems(0);
        $this->assertEmpty($this->block->toHtml());
    }

    /**
     * @magentoDbIsolation disabled
     *
     * @magentoDataFixture Magento/Customer/_files/customer.php
     *
     * @return void
     */
    public function testNotEmptyHtml(): void
    {
        $this->registry->register(
            'checkout_current_customer',
            $this->customerRepository->get('customer@example.com')
        );
        $this->registry->register('checkout_current_store', $this->storeManager->getStore('default'));
        $this->prepareCustomerSession(1);
        $this->prepareFailedItems(1);
        $this->assertNotEmpty($this->block->toHtml());
    }

    /**
     * Prepare failed items
     *
     * @param int $itemsCount
     * @return void
     */
    private function prepareFailedItems(int $itemsCount): void
    {
        $items = [];
        for ($i = 0; $i < $itemsCount; $i++) {
            $items[1][] = $this->dataObjectFactory->create(['data' => ['code' => Data::ADD_ITEM_STATUS_FAILED_SKU]]);
        }
        $this->customerSession->setAffectedItems($items);
    }

    /**
     * Prepare customer session
     *
     * @param int $customerId
     * @return void
     */
    private function prepareCustomerSession(int $customerId): void
    {
        $this->customerSession->loginById($customerId);
        $this->customerHelper->setSession($this->customerSession);
    }
}
