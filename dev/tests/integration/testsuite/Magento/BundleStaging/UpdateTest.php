<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\BundleStaging;

use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Framework\App\ResourceConnection;
use Magento\Catalog\Model\ProductRepository;

/**
 * Test saving Bundle product with a Scheduled Update
 *
 * @magentoAppArea adminhtml
 */
class UpdateTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var UpdateRepositoryInterface
     */
    private $repository;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->repository = $objectManager->create(UpdateRepositoryInterface::class);
        $this->resourceConnection = $objectManager->get(ResourceConnection::class);
        $this->productRepository = $objectManager->get(ProductRepository::class);
    }

    /**
     * Checking Database after saving Bundle product with a Scheduled Update
     *
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Staging/_files/staging_temporary_update.php
     * @magentoDataFixture Magento/Bundle/_files/PriceCalculator/fixed_bundle_product.php
     * @return void
     */
    public function testDatabaseAfterScheduledUpdate(): void
    {
        $query = $this->resourceConnection->getConnection()
            ->select()
            ->from($this->resourceConnection->getTableName('catalog_product_bundle_option_value'), 'COUNT(*)');
        $countBeforeUpdate = $this->resourceConnection->getConnection()->fetchAssoc($query);

        $bundleProduct = $this->productRepository->get('bundle_product')->setNewsFromDate(date('Y-m-d H:i:s'));
        $bundleProduct->save();

        $update = $this->repository->get(2000);
        $update->setStartTime(date('Y-m-d H:i:s', strtotime('+ 5 minutes', strtotime($update->getEndTime()))));
        $update->setEndTime('');
        $this->repository->save($update);

        $countAfterUpdate = $this->resourceConnection->getConnection()->fetchAssoc($query);
        $this->assertEquals($countBeforeUpdate, $countAfterUpdate);
    }
}
