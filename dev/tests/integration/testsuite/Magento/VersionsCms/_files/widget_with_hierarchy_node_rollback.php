<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\TestFramework\Helper\Bootstrap;
use Magento\Widget\Model\ResourceModel\Widget\Instance;
use Magento\Widget\Model\ResourceModel\Widget\Instance\CollectionFactory;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

$objectManager = Bootstrap::getObjectManager();
/** @var CollectionFactory $collectionFactory */
$collectionFactory = $objectManager->get(CollectionFactory::class);
/** @var Instance $widgetResourceModel */
$widgetResourceModel = $objectManager->get(Instance::class);
$widget = $collectionFactory->create()
    ->addFieldToFilter('title', 'Test Widget with Hierarchy node')
    ->getFirstItem();
if ($widget->getInstanceId()) {
    $widgetResourceModel->delete($widget);
}

Resolver::getInstance()
    ->requireDataFixture('Magento/VersionsCms/_files/hierarchy_node_with_default_store_rollback.php');
