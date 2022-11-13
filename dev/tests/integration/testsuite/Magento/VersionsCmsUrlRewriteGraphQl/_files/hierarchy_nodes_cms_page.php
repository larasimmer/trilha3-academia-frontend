<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Cms\Api\Data\PageInterfaceFactory;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\VersionsCms\Api\Data\HierarchyNodeInterfaceFactory;
use Magento\VersionsCms\Api\HierarchyNodeRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
/** @var PageInterfaceFactory $pageFactory */
$pageFactory = $objectManager->get(PageInterfaceFactory::class);
/** @var PageRepositoryInterface $pageRepository */
$pageRepository = $objectManager->get(PageRepositoryInterface::class);
/** @var HierarchyNodeInterfaceFactory $nodeFactory */
$nodeFactory = $objectManager->get(HierarchyNodeInterfaceFactory::class);
/** @var HierarchyNodeRepositoryInterface $nodeRepository */
$nodeRepository = $objectManager->get(HierarchyNodeRepositoryInterface::class);

$pages = [
    [
        'identifier' => 'test-page',
        'title' => 'Test Page title',
        'content' => 'Test Page content',
        'store_id' => 0,
    ]
];

foreach ($pages as $key => $page) {
    $pageModel = $pageFactory->create(['data' => $page]);
    $pageModel = $pageRepository->save($pageModel);
    $pages[$key]['page_id'] = $pageModel->getId();
}

$nodes = [
    [
        'identifier' => 'graphql-parent',
        'label' => 'GraphQl Parent Node',
        'level' => 1,
        'sort_order' => 0,
        'request_url' => 'graphql-parent',
        'xpath' => '',
        'scope' => "default",
        'scope_id' => 0
    ],
    [
        'page_id' => $pages[0]['page_id'],
        'level' => 2,
        'sort_order' => 2,
        'request_url' => 'graphql-parent/test-page',
        'xpath' => '1/',
        'scope' => "default",
        'scope_id' => 0
    ]
];

foreach ($nodes as $node) {
    $nodeModel = $nodeFactory->create(['data' => $node]);

    if (isset($parentId)) {
        $nodeModel->setParentId($parentId);
    }

    try {
        $nodeModel->setHasDataChanges(true);
        $nodeModel = $nodeRepository->save($nodeModel);
    } catch (\Magento\Framework\Exception\LocalizedException $e) {
        $tmp = 0;
    }
    $parentId = $nodeModel->getId();
}
