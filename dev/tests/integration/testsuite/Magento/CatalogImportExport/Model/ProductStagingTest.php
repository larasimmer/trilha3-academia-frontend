<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogImportExport\Model;

/**
 * @magentoDataFixtureBeforeTransaction Magento/Catalog/_files/enable_reindex_schedule.php
 */
class ProductStagingTest extends ProductTest
{
    /**
     * @inheritdoc
     */
    protected function modifyData(array $skus): void
    {
        $this->objectManager->get(\Magento\CatalogImportExport\Model\Version::class)->create($skus, $this);
    }

    /**
     * @inheritdoc
     */
    public function prepareProduct(\Magento\Catalog\Model\Product $product): void
    {
    }
}
