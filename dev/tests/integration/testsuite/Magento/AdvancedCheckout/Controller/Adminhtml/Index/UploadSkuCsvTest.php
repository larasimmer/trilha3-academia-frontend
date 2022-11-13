<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Controller\Adminhtml\Index;

use Magento\AdvancedCheckout\Helper\Data;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Filesystem;
use Magento\Framework\Message\MessageInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Store\Model\Store;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * Checks upload sku csv controller
 *
 * @see \Magento\AdvancedCheckout\Controller\Adminhtml\Index\UploadSkuCsv
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class UploadSkuCsvTest extends AbstractBackendController
{
    /** @var Session */
    private $session;

    /** @var Data */
    private $helper;

    /** @var CartRepositoryInterface */
    private $cartRepository;

    /** @var Filesystem */
    private $filesystem;

    /** @var ScopeConfigInterface */
    private $config;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->session = $this->_objectManager->get(Session::class);
        $this->helper = $this->_objectManager->get(Data::class);
        $this->cartRepository = $this->_objectManager->get(CartRepositoryInterface::class);
        $this->filesystem = $this->_objectManager->get(Filesystem::class);
        $this->config = $this->_objectManager->get(ScopeConfigInterface::class);
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
            'sku_file_uploaded' => '0',
            'add_by_sku' => [
                'simple2' => ['qty' => 1],
            ],
            'customer' => 1,
            'store' => 1,
        ];
        $this->session->loginById(1);
        $this->dispatchRequestWithData($postData);
        $this->assertQuoteItems(1, ['simple2']);
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDataFixture Magento/Catalog/_files/second_product_simple.php
     * @magentoConfigFixture default_store web/unsecure/base_link_url http://custom_default_url/
     * @magentoConfigFixture default_store web/seo/use_rewrites 0
     * @return void
     */
    public function testUploadWithDifferentDefaultStoreUrl(): void
    {
        $postData = [
            'sku_file_uploaded' => '0',
            'add_by_sku' => [
                'simple2' => ['qty' => 1],
            ],
            'customer' => 1,
            'store' => 1,
        ];
        $this->session->loginById(1);
        $server = $this->getRequest()->getServer();
        $server['HTTP_REFERER'] = $this->config->getValue(Store::XML_PATH_UNSECURE_BASE_LINK_URL);
        $this->getRequest()->setServer($server);
        $this->dispatchRequestWithData($postData);
        $this->assertRedirect($this->stringContains($this->getRequest()->getServer('HTTP_REFERER')));
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     *
     * @return void
     */
    public function testUploadWithError(): void
    {
        $postData = [
            'sku_file_uploaded' => '0',
            'add_by_sku' => [
                'unexisting_sku' => ['qty' => 1],
            ],
            'customer' => 1,
            'store' => 1,
        ];
        $this->session->loginById(1);
        $this->dispatchRequestWithData($postData);
        $items = $this->helper->getFailedItems();
        $this->assertNotNull($items);
        $item = reset($items);
        $this->assertEquals('unexisting_sku', $item->getSku());
        $this->assertEquals(Data::ADD_ITEM_STATUS_FAILED_SKU, $item->getCode());
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDataFixture Magento/Catalog/_files/second_product_simple.php
     * @magentoDataFixture Magento/Catalog/_files/product_simple_xss.php
     *
     * @return void
     */
    public function testUploadFile(): void
    {
        $_FILES['sku_file'] = $this->prepareFile('order_by_sku.csv', 'text/csv');
        $this->session->loginById(1);
        $this->dispatchRequestWithData(
            [Data::REQUEST_PARAMETER_SKU_FILE_IMPORTED_FLAG => true],
            ['customer' => 1, 'store' => 1]
        );
        $this->assertQuoteItems(1, ['simple2', 'product-with-xss']);
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @dataProvider uploadInvalidFileDataProvider
     *
     * @param string $filename
     * @param string $fileType
     * @param string $errorMessage
     * @return void
     */
    public function testUploadInvalidFile(string $filename, string $fileType, string $errorMessage): void
    {
        $this->session->loginById(1);
        $_FILES['sku_file'] = $this->prepareFile($filename, $fileType);
        $this->dispatchRequestWithData(
            [Data::REQUEST_PARAMETER_SKU_FILE_IMPORTED_FLAG => true],
            ['customer' => 1, 'store' => 1]
        );
        $this->assertSessionMessages(
            $this->equalTo([$errorMessage]),
            MessageInterface::TYPE_ERROR
        );
    }

    /**
     * @return array
     */
    public function uploadInvalidFileDataProvider(): array
    {
        return [
            'wrongFileExtension' => [
                'filename' => 'image.jpg',
                'fileType' => 'image/jpeg',
                'errorMessage' => 'Please upload the file in .csv format.',
            ],
            'emptyFile' => [
                'filename' => 'order_by_sku_empty.csv',
                'fileType' => 'text/csv',
                'errorMessage' => 'The file is corrupt and can\'t be used.',
            ],
            'noDataFile' => [
                'filename' => 'order_by_sku_no_data.csv',
                'fileType' => 'text/csv',
                'errorMessage' => 'The file is corrupt and can\'t be used.',
            ],
            'misspelledFile' => [
                'filename' => 'order_by_sku_misspelled.csv',
                'fileType' => 'text/csv',
                'errorMessage' => 'The file is corrupt and can\'t be used.',
            ],
        ];
    }

    /**
     * @return void
     */
    public function testWithoutCustomer(): void
    {
        $this->dispatchRequestWithData([]);
        $this->assertRedirect($this->stringContains('customer/index'));
    }

    /**
     * Dispatch request with params
     *
     * @param array $post
     * @param array $params
     * @return void
     */
    private function dispatchRequestWithData(array $post, array $params = []): void
    {
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setParams($params);
        $this->getRequest()->setPostValue($post);
        $this->dispatch('backend/checkout/index/uploadSkuCsv/');
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
        $fixtureDir = realpath(__DIR__ . '/../../../_files');
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
     * Assert quote items
     *
     * @param int $customerId
     * @param array $itemsSkus
     * @param int $storeId
     * @return void
     */
    private function assertQuoteItems(int $customerId, array $itemsSkus, int $storeId = 1): void
    {
        $quote = $this->cartRepository->getForCustomer($customerId, [$storeId]);
        $quoteItems = $quote->getItemsCollection()->addFieldToFilter(ProductInterface::SKU, ['in' => $itemsSkus]);
        $this->assertCount(count($itemsSkus), $quoteItems);
    }
}
