<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;
use Magento\VersionsCms\Api\HierarchyNodeRepositoryInterface;
use Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node\Collection as NodeCollection;
use Magento\VersionsCms\Model\Hierarchy\Node;

$objectManager = Bootstrap::getObjectManager();
/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var HierarchyNodeRepositoryInterface $nodeRepository */
$nodeRepository = $objectManager->get(HierarchyNodeRepositoryInterface::class);
/** @var NodeCollection $collection */
$collection = $objectManager->create(NodeCollection::class);
/** @var Node $node */
foreach ($collection->getItems() as $node) {
    $nodeRepository->delete($node);
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);

Resolver::getInstance()->requireDataFixture('Magento/Cms/_files/pages_rollback.php');
Resolver::getInstance()
    ->requireDataFixture('Magento/Store/_files/second_website_with_store_group_and_store_rollback.php');
