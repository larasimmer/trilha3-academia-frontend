<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

\Magento\TestFramework\Helper\Bootstrap::getObjectManager()
    ->create(\Magento\CatalogPermissions\Model\Indexer\Category\Processor::class)
    ->reindexAll();

\Magento\TestFramework\Helper\Bootstrap::getObjectManager()
    ->create(\Magento\CatalogPermissions\Model\Indexer\Product\Processor::class)
    ->reindexAll();
