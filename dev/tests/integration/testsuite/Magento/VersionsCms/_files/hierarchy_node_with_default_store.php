<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\TestFramework\Helper\Bootstrap;
use Magento\VersionsCms\Api\Data\HierarchyNodeInterfaceFactory;
use Magento\VersionsCms\Api\HierarchyNodeRepositoryInterface;

$objectManager = Bootstrap::getObjectManager();

/** @var HierarchyNodeInterfaceFactory $nodeFactory */
$nodeFactory = $objectManager->get(HierarchyNodeInterfaceFactory::class);
/** @var HierarchyNodeRepositoryInterface $nodeRepository */
$nodeRepository = $objectManager->get(HierarchyNodeRepositoryInterface::class);
$node = $nodeFactory->create();
$node->setIdentifier('simple_node')
    ->setLabel('Simple test Node')
    ->setScope('default')
    ->setRequestUrl('home');
$nodeRepository->save($node);
