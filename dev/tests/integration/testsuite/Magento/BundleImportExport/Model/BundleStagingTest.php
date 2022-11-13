<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\BundleImportExport\Model;

class BundleStagingTest extends BundleTest
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
        $extensionAttributes->setBundleProductOptions([]);
        $product->setExtensionAttributes($extensionAttributes);
    }
}
