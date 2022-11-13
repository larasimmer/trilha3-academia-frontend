<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Cms\Api\Data\PageInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;
use Magento\VersionsCms\Api\Data\HierarchyNodeInterfaceFactory;
use Magento\VersionsCms\Api\HierarchyNodeRepositoryInterface;
use Magento\VersionsCms\Model\Hierarchy\Node;

Resolver::getInstance()->requireDataFixture('Magento/Cms/_files/pages.php');
Resolver::getInstance()->requireDataFixture('Magento/Store/_files/second_website_with_store_group_and_store.php');

$objectManager = Bootstrap::getObjectManager();
/** @var PageInterface $page */
$page = $objectManager->create(PageInterface::class);
$page->load('page100');

/** @var HierarchyNodeRepositoryInterface $nodeRepository */
$nodeRepository = $objectManager->get(HierarchyNodeRepositoryInterface::class);
/** @var HierarchyNodeInterfaceFactory $nodeFactory */
$nodeFactory = $objectManager->get(HierarchyNodeInterfaceFactory::class);

$firstNode = $nodeFactory->create();
$firstNode
    ->setPageId($page->getId())
    ->setLevel(1)
    ->setSortOrder(0)
    ->setRequestUrl($page->getIdentifier())
    ->setScope(Node::NODE_SCOPE_DEFAULT)
    ->setScopeId(Node::NODE_SCOPE_DEFAULT_ID);
$nodeRepository->save($firstNode);

/** @var WebsiteRepositoryInterface $websiteRepository */
$websiteRepository = $objectManager->get(WebsiteRepositoryInterface::class);
$website = $websiteRepository->get('test');
$secondNode = $nodeFactory->create();
$secondNode
    ->setPageId($page->getId())
    ->setLevel(1)
    ->setSortOrder(0)
    ->setRequestUrl($page->getIdentifier())
    ->setScope(Node::NODE_SCOPE_WEBSITE)
    ->setScopeId($website->getId());
$nodeRepository->save($secondNode);

$storeId = current($website->getStoreIds());
$thirdNode = $nodeFactory->create();
$thirdNode
    ->setPageId($page->getId())
    ->setLevel(1)
    ->setSortOrder(0)
    ->setRequestUrl($page->getIdentifier())
    ->setScope(Node::NODE_SCOPE_STORE)
    ->setScopeId($storeId);
$nodeRepository->save($thirdNode);
