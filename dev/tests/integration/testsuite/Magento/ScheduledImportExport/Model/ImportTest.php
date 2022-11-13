<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ScheduledImportExport\Model;

use LogicException;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\ScheduledImportExport\Model\Scheduled\Operation;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Indexer\TestCase;

/**
 * Test for schedule import
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @magentoDbIsolation disabled
 */
class ImportTest extends TestCase
{
    /**
     * @var string[]
     */
    private $importedProductSkus = [];

    /**
     * @var Filesystem\Directory\WriteInterface
     */
    private $directory;

    /**
     * @inheritDoc
     *
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    protected function setUp(): void
    {
        $fileSystem = Bootstrap::getObjectManager()->get(Filesystem::class);
        $this->directory = $fileSystem->getDirectoryWrite(DirectoryList::VAR_IMPORT_EXPORT);
        $this->directory->create('/tmp');
    }

    /**
     * Setup before class
     *
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        $db = Bootstrap::getInstance()->getBootstrap()
            ->getApplication()
            ->getDbInstance();
        if (!$db->isDbDumpExists()) {
            throw new LogicException('DB dump does not exist.');
        }
        $db->restoreFromDbDump();

        /** @var Filesystem $fileSystem */

        parent::setUpBeforeClass();
    }

    /**
     * Test run schedule
     *
     * @return void
     */
    public function testRunSchedule(): void
    {
        $this->directory->getDriver()->filePutContents(
            $this->directory->getAbsolutePath('/tmp/product.csv'),
            file_get_contents(__DIR__ . '/../_files/product.csv')
        );

        $importedProductSku = 'product_100500';
        $this->assertNull($this->getProduct($importedProductSku));
        $this->doImport(
            [
                'file_name' => 'product.csv',
                'server_type' => 'file',
                'file_path' => $this->directory->getAbsolutePath('/tmp'),
            ]
        );

        $this->assertNotNull($this->getProduct($importedProductSku));
        $this->importedProductSkus[] = $importedProductSku;
    }

    /**
     * Test run schedule with utf8 encoded file
     *
     * @return void
     */
    public function testRunScheduleWithUTF8EncodedFile(): void
    {
        $this->assertNull($this->getProduct('product_100501'));
        /** @var Filesystem $fileSystem */
        $tmpFilename = uniqid('test_import_') . '.csv';
        $byteOrderMak = pack('CCC', 0xef, 0xbb, 0xbf);
        $content = file_get_contents(__DIR__ . '/../_files/product.csv');
        //change sku suffix to make sure a new product is created
        $content = str_replace('100500', '100501', $content);
        $content = $byteOrderMak . utf8_encode($content);
        $this->directory->getDriver()->filePutContents(
            $this->directory->getAbsolutePath('/tmp/' . $tmpFilename),
            $content
        );
        $this->doImport(
            [
                'file_name' => $tmpFilename,
                'server_type' => 'file',
                'file_path' => $this->directory->getAbsolutePath('/tmp'),
            ]
        );
        $this->assertNotNull($this->getProduct('product_100501'));
        $this->importedProductSkus = [
            'product_100500',
            'product_100501',
        ];
    }

    /**
     * @param array $fileInfo
     */
    private function doImport(array $fileInfo): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $model = Bootstrap::getObjectManager()->create(
            Import::class,
            [
                'data' => [
                    'entity' => 'catalog_product',
                    'behavior' => 'append',
                ],
            ]
        );
        $operation = $objectManager->create(Operation::class);
        $operation->setFileInfo($fileInfo);
        $model->runSchedule($operation);
    }

    /**
     * Get product by sku
     *
     * @param string $sku
     * @return Product|null
     */
    private function getProduct(string $sku): ?Product
    {
        parent::tearDown();
        /** @var ProductRepository $productRepository */
        $productRepository = Bootstrap::getObjectManager()->get(ProductRepository::class);
        try {
            $product = $productRepository->get($sku, false, null, true);
        } catch (NoSuchEntityException $exception) {
            $product = null;
        }
        return $product;
    }

    /**
     * @inheritDoc
     *
     * @param string[] $skus
     * @return void
     */
    protected function tearDown(): void
    {
        if (!empty($this->importedProductSkus)) {
            $objectManager = Bootstrap::getObjectManager();
            /** @var ProductRepositoryInterface $productRepository */
            $productRepository = $objectManager->create(ProductRepositoryInterface::class);
            $registry = $objectManager->get(\Magento\Framework\Registry::class);
            /** @var ProductRepositoryInterface $productRepository */
            $registry->unregister('isSecureArea');
            $registry->register('isSecureArea', true);

            foreach ($this->importedProductSkus as $sku) {
                try {
                    $productRepository->deleteById($sku);
                } catch (NoSuchEntityException $e) {
                    // product already deleted
                }
            }
            $registry->unregister('isSecureArea');
            $registry->register('isSecureArea', false);
        }
        $this->directory->delete('/tmp');
    }
}
