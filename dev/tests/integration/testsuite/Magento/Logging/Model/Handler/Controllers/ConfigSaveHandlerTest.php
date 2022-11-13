<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Logging\Model\Handler\Controllers;

use Magento\TestFramework\Helper\Bootstrap;

class ConfigSaveHandlerTest extends \Magento\TestFramework\TestCase\AbstractBackendController
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        /** @var \Magento\Framework\App\DeploymentConfig $deploymentConfig */
        $deploymentConfig = Bootstrap::getObjectManager()->create(\Magento\Framework\App\DeploymentConfig::class);
        $adminFrontname = $deploymentConfig->get(
            \Magento\Backend\Setup\ConfigOptionsList::CONFIG_PATH_BACKEND_FRONTNAME
        );
        $this->uri = $adminFrontname . '/admin/system_config/save/';

        $this->resource = 'Magento_Catalog::config_catalog';
        $this->httpMethod = 'POST';
    }

    /**
     * @magentoAppArea adminhtml
     * @magentoDbIsolation enabled
     * @magentoConfigFixture default/catalog/recently_products/recently_viewed_lifetime 1000
     */
    public function testCreatingConfigSaveLogEntries(): void
    {
        $newRecentlyViewedLifetimeValue = "500";

        $postData = [
            'config_state' => [
                'recently_products' => '1',
                'frontend' => '1'
            ],
            'groups' => [
                'recently_products' => [
                    'fields' => [
                        'recently_viewed_lifetime' => [
                            'value' => $newRecentlyViewedLifetimeValue
                        ]
                    ]
                ],
                'frontend' => []
            ]
        ];

        $this->getRequest()->setMethod('POST');
        $this->getRequest()->setParam('section', 'catalog');
        $this->getRequest()->setPostValue($postData);

        $this->dispatch($this->uri);

        /** @var \Magento\Logging\Model\ResourceModel\Event\Collection $eventCollection */
        $eventCollection = $this->objectManager->create(\Magento\Logging\Model\ResourceModel\Event\Collection::class);

        $eventCollection->addFieldToFilter('fullaction', ['eq' => 'adminhtml_system_config_save'])
            ->addFieldToFilter('info', ['eq' => '{"general":"catalog"}'])
            ->setOrder('log_id')
            ->setPageSize(1)
            ->load();

        $event = $eventCollection->getFirstItem();

        if (!$event->getLogId()) {
            $this->fail('Configuration change event has not been logged.');
        }

        /** @var \Magento\Logging\Model\ResourceModel\Event\Changes\Collection $eventChangesCollection */
        $eventChangesCollection = $this->objectManager->create(
            \Magento\Logging\Model\ResourceModel\Event\Changes\Collection::class
        );

        $eventChangesCollection->addFieldToFilter('event_id', ['eq' => $event->getLogId()])
            ->addFieldToFilter('source_name', ['eq' => 'recently_products'])
            ->setOrder('event_id')
            ->setPageSize(1)
            ->load();

        $eventChanges = $eventChangesCollection->getFirstItem();

        if (!$event->getId()) {
            $this->fail('Configuration change event changes have not been logged.');
        }

        $this->assertSame('{"recently_viewed_lifetime":"1000"}', $eventChanges->getOriginalData());
        $this->assertSame(
            sprintf('{"recently_viewed_lifetime":"%s"}', $newRecentlyViewedLifetimeValue),
            $eventChanges->getResultData()
        );
    }

    public function testAclHasAccess(): void
    {
        // This check is the responsibility of the Magento_Config module
    }

    /**
     * Test ACL actually denying access.
     */
    public function testAclNoAccess(): void
    {
        // This check is the responsibility of the Magento_Config module
    }
}