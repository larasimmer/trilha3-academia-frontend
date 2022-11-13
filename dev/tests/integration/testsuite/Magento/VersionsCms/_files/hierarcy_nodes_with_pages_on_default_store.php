<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Cms\Model\Page;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\VersionsCms\Model\Hierarchy\Node;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
/** @var StoreRepositoryInterface $storeRepository */
$storeRepository = $objectManager->get(StoreRepositoryInterface::class);
$store = $storeRepository->get('default');
//load page
$page = $objectManager->create(Page::class);
$page->load('page_design_blank');
$page->setStores([0, $store->getId()]);
$page->save();

//uncheck "use default" checkbox for second store
$defaultStoreRootNode = $objectManager->create(Node::class);
$defaultStoreRootNode->setScope(Node::NODE_SCOPE_STORE)
    ->setScopeId($store->getId())
    ->setSortOrder(0)
    ->setPageId(null)
    ->setParentNodeId(null)
    ->setIdentifier(null)
    ->setLabel(null)
    ->setRequestUrl(null)
    ->setLevel(0)
    ->save();
//create main node for second store
$defaultStoreMainNode = $objectManager->create(Node::class);
$defaultStoreMainNode->setScope(Node::NODE_SCOPE_STORE)
    ->setScopeId($store->getId())
    ->setSortOrder(0)
    ->setPageId(null)
    ->setParentNodeId(null)
    ->setIdentifier('default_store_main_node')
    ->setLabel('Default Store Main node')
    ->setRequestUrl('default_store_main_node')
    ->setLevel(1)
    ->save();
//add page node to default store main node
$defaultStorePageNode = $objectManager->create(Node::class);
$defaultStorePageNode->setScope(Node::NODE_SCOPE_STORE)
    ->setScopeId($store->getId())
    ->setSortOrder(0)
    ->setPageId($page->getId())
    ->setParentNodeId($defaultStoreMainNode->getId())
    ->setIdentifier(null)
    ->setLabel(null)
    ->setRequestUrl($defaultStoreMainNode->getIdentifier() . '/' . $page->getIdentifier())
    ->setLevel(2)
    ->save();
//set correct xpath
$defaultStorePageNode->setXpath($defaultStoreMainNode->getId() . '/' . $defaultStorePageNode->getId())
    ->save();
