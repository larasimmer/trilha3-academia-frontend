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
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/Cms/_files/pages.php');
Resolver::getInstance()->requireDataFixture('Magento/Store/_files/store.php');

$objectManager = Bootstrap::getObjectManager();
/** @var StoreRepositoryInterface $storeRepository */
$storeRepository = $objectManager->get(StoreRepositoryInterface::class);
$store = $storeRepository->get('test');
//load page
$page = $objectManager->create(Page::class);
$page->load('page100');
$page->setStores([0, $store->getId()]);
$page->save();
//main node data
$mainNodeData = [
    'parent_node_id' => null,
    'page_id' => null,
    'identifier' => 'main',
    'label' => 'Main node',
    'level' => 1,
    'sort_order' => 0,
    'scope' => "default",
    'scope_id' => 0,
    'request_url' => 'main',
];
//create main node for all store view
$mainNode = $objectManager->create(Node::class);
$mainNode->setData($mainNodeData)
    ->save();
//uncheck "use default" checkbox for second store
$uncheckUseDefaultForSecondStore = $objectManager->create(Node::class);
$uncheckUseDefaultForSecondStore->setData($mainNodeData)
    ->setScope('store')
    ->setScopeId($store->getId())
    ->setIdentifier(null)
    ->setLabel(null)
    ->setRequestUrl(null)
    ->setLevel(0)
    ->save();
//create main node for second store
$mainNodeForSecondStore = $objectManager->create(Node::class);
$mainNodeForSecondStore->setData($mainNodeData)
    ->setScope('store')
    ->setScopeId($store->getId())
    ->save();

foreach ([$mainNode, $mainNodeForSecondStore] as $parentNode) {
    //create page node assigned to main node in "all store view" and "test store" scopes
    $pageNodeData = [
        'parent_node_id' => $parentNode->getId(),
        'page_id' => $page->getId(),
        'identifier' => null,
        'label' => null,
        'level' => 2,
        'sort_order' => 0,
        'scope' => $parentNode->getScope(),
        'scope_id' => $parentNode->getScopeId(),
        'request_url' => 'main/' . $page->getIdentifier(),
    ];
    $pageNode = $objectManager->create(Node::class);
    $pageNode->setData($pageNodeData)->save();
    //set correct xpath
    $xpath = $mainNode->getId() . '/' . $pageNode->getId();
    $pageNode->setXpath($xpath)->save();
}
