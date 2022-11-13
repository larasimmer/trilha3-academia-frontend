<?php
/***
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Logging\Controller\Adminhtml\Logging;

class DownloadTest extends \Magento\TestFramework\TestCase\AbstractBackendController
{
    protected function setUp(): void
    {
        $this->resource = 'Magento_Logging::backups';
        $this->uri = 'backend/admin/logging/download';
        parent::setUp();
        $this->getRequest()->setParams(['basename' => 'some_base_name']);
    }
}
