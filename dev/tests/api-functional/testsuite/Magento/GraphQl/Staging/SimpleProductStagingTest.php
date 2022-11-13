<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Staging;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\CatalogStaging\Api\ProductStagingInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Model\ResourceModel\Db\UpdateEntityRow;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Stage a Simple product
 */
class SimpleProductStagingTest extends GraphQlAbstract
{
    /** @var  UpdateRepositoryInterface */
    private $updateRepositoryInterface;

    /** @var  ProductRepositoryInterface */
    private $productRepository;

    /** @var  ProductStagingInterface */
    private $productStaging;

    /** @var  MetadataPool */
    private $entityMetadataPool;

    /** @var  UpdateEntityRow */
    private $updateEntityRow;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->updateRepositoryInterface = $objectManager->get(UpdateRepositoryInterface::class);
        $this->productRepository = $objectManager->get(ProductRepositoryInterface::class);
        $this->productStaging = $objectManager->get(ProductStagingInterface::class);
        $this->entityMetadataPool = $objectManager->get(MetadataPool::class);
        $this->updateEntityRow = $objectManager->get(UpdateEntityRow::class);
    }

    /**
     * Query for simple product after creating a scheduled update for it
     *
     * @magentoApiDataFixture Magento/Staging/_files/staging_temporary_update.php
     * @magentoApiDataFixture Magento/Catalog/_files/multiple_products_staged.php
     */
    public function testStageASimpleProduct()
    {
        $update = $this->updateRepositoryInterface->get(2000);
        $update->setStartTime(date('Y-m-d H:i:s', strtotime('+ 5 minutes', strtotime($update->getEndTime()))));
        $update->setEndTime('');
        $this->updateRepositoryInterface->save($update);
        $version = $update->getId();
        /** @var Product $simpleProduct */
        $simpleProduct = $this->productRepository->get('simple1')->setNewsFromDate(date('Y-m-d H:i:s'));
        $this->productRepository->get('simple1')->setName('new simple product update');
        $this->productRepository->get('simple1')->setPrice(8);
        $this->productRepository->save($simpleProduct);

        $productMetadata = $this->entityMetadataPool->getMetadata(ProductInterface::class);
        $linkField = $productMetadata->getLinkField();
        $previousRowId = $simpleProduct->getData($linkField);

        $this->productStaging->schedule($simpleProduct, $version, []);
        $data = [
            $linkField => $previousRowId,
            'updated_in' => $version
        ];
        $this->updateEntityRow->execute(ProductInterface::class, $data);

        $query
            = <<<QUERY
{products
(filter:{sku:{eq:"simple1"}})
  {
    items{
      sku
      staged
      name
      id
    }
  }
}
QUERY;
        $response = $this->graphQlQuery($query);
        $this->assertArrayNotHasKey('errors', $response, 'Response has errors');
        $this->assertArrayHasKey('products', $response);
        //product['id'] should return the entity_id ,not the row_id
        $this->assertEquals($simpleProduct->getEntityId(), $response['products']['items'][0]['id']);
        //product not considered staged unless value of created_in(or currentVersion) > current_time
        $this->assertEquals(false, $response['products']['items'][0]['staged']);
    }
}
