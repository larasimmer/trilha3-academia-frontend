<?php
/***
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SalesArchive\Controller\Adminhtml\Archive;

/**
 * Testing adding to archive.
 *
 * @magentoAppArea adminhtml
 */
class AddTest extends \Magento\TestFramework\TestCase\AbstractBackendController
{
    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->resource = 'Magento_SalesArchive::add';
        $this->uri = 'backend/sales/archive/add';
        parent::setUp();
    }
}
