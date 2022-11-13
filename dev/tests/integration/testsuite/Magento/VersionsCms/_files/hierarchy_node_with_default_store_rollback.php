<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\TestFramework\Helper\Bootstrap;
use Magento\VersionsCms\Api\Data\HierarchyNodeInterfaceFactory;
use Magento\VersionsCms\Api\HierarchyNodeRepositoryInterface;
use Magento\VersionsCms\Model\Hierarchy\Node;
use Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node as NodeResource;

$objectManager = Bootstrap::getObjectManager();
/** @var HierarchyNodeInterfaceFactory $nodeFactory */
$nodeFactory = $objectManager->get(HierarchyNodeInterfaceFactory::class);
/** @var HierarchyNodeRepositoryInterface $nodeRepository */
$nodeRepository = $objectManager->get(HierarchyNodeRepositoryInterface::class);
/** @var NodeResource $nodeResource */
$nodeResource = $objectManager->get(NodeResource::class);

$node = $nodeFactory->create();
$nodeResource->load($node, 'simple_node', Node::IDENTIFIER);
if ($node->getId()) {
    $nodeRepository->delete($node);
}
