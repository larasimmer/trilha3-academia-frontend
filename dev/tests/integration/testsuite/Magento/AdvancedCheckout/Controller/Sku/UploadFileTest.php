<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Controller\Sku;

use Magento\AdvancedCheckout\Helper\Data;
use Magento\CatalogPermissions\Model\Indexer\Category;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Filesystem;
use Magento\Quote\Model\Quote\Item;
use Magento\TestFramework\TestCase\AbstractController;
use Magento\Customer\Model\Session;

/**
 * Class checks upload file controller behaviour
 *
 * @see \Magento\AdvancedCheckout\Controller\Sku\UploadFile
 *
 * @magentoAppArea frontend
 */
class UploadFileTest extends AbstractController
{
    /** @var Session */
    private $session;

    /** @var Data */
    private $helper;

    /** @var Category */
    private $indexer;

    /** @var Filesystem */
    private $filesystem;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->session = $this->_objectManager->get(Session::class);
        $this->helper = $this->_objectManager->get(Data::class);
        $this->indexer = $this->_objectManager->get(Category::class);
        $this->filesystem = $this->_objectManager->get(Filesystem::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        $this->session->logout();

        parent::tearDown();
    }

    /**
     * Tests that product can not be added to Shopping Cart via Order by SKU as of Catalog Permission settings.
     * Product belongs to category. Access to this category is denied for all customer groups.
     * After Order by SKU product should appear in Products Requiring Attention section with error message.
     *
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/grant_catalog_category_view 1
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/grant_catalog_product_price 1
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/grant_checkout_items 1
     * @magentoDataFixture Magento/CatalogPermissions/_files/reindex_permissions.php
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDataFixture Magento/Catalog/_files/categories.php
     * @magentoDataFixture Magento/CatalogPermissions/_files/permission.php
     * @magentoDbIsolation disabled
     *
     * @return void
     */
    public function testOrderBySkuProductDeniedByCatalogPermissions(): void
    {
        $postData = [
            'items' => [
                ['sku' => '12345-1'],
            ],
        ];
        $this->indexer->executeFull();
        $this->session->loginById(1);
        $this->dispatchRequestWithData($postData);
        $this->assertSessionMessages(
            $this->containsEqual((string)__('%1 product requires your attention.', 1))
        );
        $cartFailedItem = $this->getCartFailedItem();
        $this->assertNotNull(
            $cartFailedItem,
            'Product should be present in cart failed items list'
        );
        $this->assertEquals(
            Data::ADD_ITEM_STATUS_FAILED_PERMISSIONS,
            $cartFailedItem->getCode(),
            'Cart item should have failed permissions code'
        );
    }

    /**
     * @return void
     */
    public function testUploadFileWithoutCustomer(): void
    {
        $post = [
            'items' => [
                ['sku' => 'simple', 'qty' => 1],
            ],
        ];
        $this->dispatchRequestWithData($post);
        $this->assertRedirect($this->stringContains('customer/account/login'));
    }

    /**
     * @magentoConfigFixture current_store sales/product_sku/my_account_enable 0
     *
     * @magentoDataFixture Magento/Customer/_files/customer.php
     *
     * @return void
     */
    public function testUploadDisabledOrderBySku(): void
    {
        $post = [
            'items' => [
                ['sku' => 'simple', 'qty' => 1],
            ],
        ];
        $this->session->loginById(1);
        $this->dispatchRequestWithData($post);
        $this->assertRedirect($this->stringContains('customer/account'));
    }

    /**
     * @magentoConfigFixture current_store sales/product_sku/my_account_enable 2
     * @magentoConfigFixture current_store sales/product_sku/allowed_groups 101
     *
     * @magentoDataFixture Magento/Customer/_files/customer.php
     *
     * @return void
     */
    public function testUploadDisabledOrderBySkuForCurrentGroup(): void
    {
        $post = [
            'items' => [
                ['sku' => 'simple', 'qty' => 1],
            ],
        ];
        $this->session->loginById(1);
        $this->dispatchRequestWithData($post);
        $this->assertRedirect($this->stringContains('customer/account'));
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     *
     * @return void
     */
    public function testUploadInvalidFile(): void
    {
        $_FILES['sku_file'] = $this->prepareFile('image.jpg', 'image/jpeg');

        $this->session->loginById(1);
        $this->dispatchRequestWithData([
            Data::REQUEST_PARAMETER_SKU_FILE_IMPORTED_FLAG => true,
        ]);
        $this->assertSessionMessages($this->containsEqual((string)__('Please upload the file in .csv format.')));
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDataFixture Magento/Catalog/_files/second_product_simple.php
     * @magentoDataFixture Magento/Catalog/_files/product_simple_xss.php
     *
     * @return void
     */
    public function testUploadValidFile(): void
    {
        $expectedItems = [['qty' => '1', 'sku' => 'simple2'], ['qty' => '2', 'sku' => 'product-with-xss']];
        $_FILES['sku_file'] = $this->prepareFile('order_by_sku.csv', 'text/csv');

        $this->session->loginById(1);
        $this->dispatchRequestWithData([
            Data::REQUEST_PARAMETER_SKU_FILE_IMPORTED_FLAG => true,
        ]);
        $this->assertSessionMessages(
            $this->containsEqual((string)__('You added %1 products to your shopping cart.', 2))
        );
        $this->assertRedirect($this->stringContains('checkout/cart'));
        $this->assertEquals($expectedItems, $this->getRequest()->getParam('items'));
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDataFixture Magento/Catalog/_files/second_product_simple.php
     *
     * @return void
     */
    public function testUploadItemsFromRequest(): void
    {
        $postData = [
            'items' => [
                ['sku' => 'simple2', 'qty' => 1],
            ],
        ];
        $this->session->loginById(1);
        $this->dispatchRequestWithData($postData);
        $this->assertSessionMessages(
            $this->containsEqual((string)__('You added %1 product to your shopping cart.', 1))
        );
        $this->assertRedirect($this->stringContains('checkout/cart'));
    }

    /**
     * Prepare file
     *
     * @param string $fileName
     * @param string $type
     * @return array
     */
    private function prepareFile(string $fileName, string $type): array
    {
        $tmpDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::SYS_TMP);
        $fixtureDir = realpath(__DIR__ . '/../../_files');
        $filePath = $tmpDirectory->getAbsolutePath($fileName);
        copy($fixtureDir . DIRECTORY_SEPARATOR . $fileName, $filePath);

        return [
            'name' => $fileName,
            'type' => $type,
            'tmp_name' => $filePath,
            'error' => 0,
            'size' => filesize($filePath),
        ];
    }

    /**
     * Returns cart failed item.
     *
     * @return Item|null
     */
    private function getCartFailedItem(): ?Item
    {
        $cartFailedItem = $this->helper->getFailedItems();

        return array_pop($cartFailedItem);
    }

    /**
     * Dispatch post request with post data
     *
     * @param array $post
     * @return void
     */
    private function dispatchRequestWithData(array $post): void
    {
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue($post);
        $this->dispatch('customer_order/sku/uploadFile/');
    }
}
