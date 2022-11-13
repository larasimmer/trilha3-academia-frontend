<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Cms\Api\Data\PageInterfaceFactory;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Store\Model\Store;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\VersionsCms\Api\Data\HierarchyNodeInterfaceFactory;
use Magento\VersionsCms\Api\HierarchyNodeRepositoryInterface;

$objectManager = Bootstrap::getObjectManager();
/** @var PageInterfaceFactory $pageFactory */
$pageFactory = $objectManager->get(PageInterfaceFactory::class);
/** @var PageRepositoryInterface $pageRepository */
$pageRepository = $objectManager->get(PageRepositoryInterface::class);

$firstPage = $pageFactory->create();
$firstPage->setIdentifier('page-1');
$firstPage->setTitle('Page 1');
$firstPage->setContent('page 1 test content');
$firstPage->setStoreId([Store::DEFAULT_STORE_ID]);
$firstPage->setPageLayout('1column');
$pageRepository->save($firstPage);

$secondPage = $pageFactory->create();
$secondPage->setIdentifier('page-2');
$secondPage->setTitle('Page 2');
$secondPage->setStoreId([Store::DEFAULT_STORE_ID]);
$secondPage->setContent('page 2 test content');
$secondPage->setPageLayout('1column');
$pageRepository->save($secondPage);
/** @var HierarchyNodeInterfaceFactory $nodeFactory */
$nodeFactory = $objectManager->get(HierarchyNodeInterfaceFactory::class);
/** @var HierarchyNodeRepositoryInterface $nodeRepository */
$nodeRepository = $objectManager->get(HierarchyNodeRepositoryInterface::class);

$parentNode = $nodeFactory->create();
$parentNode->setPageId($firstPage->getId());
$parentNode->setIdentifier('test_node_1');
$parentNode->setScopeId(0);
$parentNode->setScope('default');
$parentNode->setLevel(1);
$parentNode->setLabel('Node 1');
$parentNode->setSortOrder(3);
$parentNode->setTopMenuVisibility(1);
$parentNode->setMenuVisibility(1);
$parentNode->setPagerVisibility(1);
$parentNode->setRequestUrl('page-1');
$nodeRepository->save($parentNode);
$parentNode->setXpath($parentNode->getId());
$nodeRepository->save($parentNode);

$childNode = $nodeFactory->create();
$childNode->setPageId($secondPage->getId());
$childNode->setIdentifier('test_node_2');
$childNode->setScopeId(0);
$childNode->setScope('default');
$childNode->setLevel(2);
$childNode->setLabel('Node 2');
$childNode->setSortOrder(3);
$childNode->setParentId($parentNode->getId());
$childNode->setTopMenuVisibility(1);
$childNode->setMenuVisibility(1);
$childNode->setRequestUrl('page-1/page-2');
$nodeRepository->save($childNode);
$childNode->setXpath($parentNode->getId() . '/');
$nodeRepository->save($childNode);
