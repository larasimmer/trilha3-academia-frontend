<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GroupedImportExport\Model;

class GroupedStagingTest extends GroupedTest
{
    /**
     * @inheritdoc
     */
    protected function modifyData(array $skus): void
    {
        $this->objectManager->get(\Magento\CatalogImportExport\Model\Version::class)->create($skus, $this);
    }
}
