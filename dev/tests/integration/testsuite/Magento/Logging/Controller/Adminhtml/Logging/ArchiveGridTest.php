<?php
/***
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Logging\Controller\Adminhtml\Logging;

/**
 * Testing archives.
 *
 * @magentoAppArea adminhtml
 */
class ArchiveGridTest extends \Magento\TestFramework\TestCase\AbstractBackendController
{
    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->resource = 'Magento_Logging::backups';
        $this->uri = 'backend/admin/logging/archivegrid';
        parent::setUp();
    }
}
