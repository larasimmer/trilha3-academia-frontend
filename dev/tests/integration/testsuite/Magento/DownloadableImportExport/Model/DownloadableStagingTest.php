<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\DownloadableImportExport\Model;

/**
 * Test import-export downloadable products with staging
 */
class DownloadableStagingTest extends DownloadableTest
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
        $extensionAttributes = $product->getExtensionAttributes();
        $extensionAttributes->setDownloadableProductLinks([]);
        $extensionAttributes->setDownloadableProductSamples([]);
        $product->setExtensionAttributes($extensionAttributes);
    }
}
