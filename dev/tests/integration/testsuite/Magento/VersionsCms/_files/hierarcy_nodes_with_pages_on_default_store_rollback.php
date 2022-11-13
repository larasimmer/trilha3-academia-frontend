<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node\Collection as NodeCollection;
use Magento\VersionsCms\Model\Hierarchy\Node;

$objectManager = Bootstrap::getObjectManager();
/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var NodeCollection $collection */
$collection = $objectManager->create(NodeCollection::class);
foreach ($collection->getItems() as $node) {
    /** @var Node $node */
    $node->delete();
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
