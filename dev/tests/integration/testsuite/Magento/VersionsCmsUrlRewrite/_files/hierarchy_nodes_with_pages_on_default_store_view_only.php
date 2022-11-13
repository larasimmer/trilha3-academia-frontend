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
        'identifier' => 'page-1',
        'title' => 'Page 1',
        'content' => 'some content',
        'store_id' => 1,
    ],
    [
        'identifier' => 'page-2',
        'title' => 'Page 1',
        'content' => 'some content2',
        'store_id' => 1,
    ],
];

$nodes = [];

foreach ($pages as $key => $page) {
    $pageModel = $pageFactory->create(['data' => $page]);
    $pageModel = $pageRepository->save($pageModel);
    $pages[$key]['page_id'] = $pageModel->getId();
}

$nodes = [
    [
        'page_id' => $pages[0]['page_id'],
        'identifier' => $pages[0]['identifier'],
        'label' => $pages[0]['title'],
        'request_url' => 'page-1',
        'level' => 1,
        'sort_order' => 1,
        'xpath' => '',
        'scope' => "store",
        'scope_id' => 1
    ],
    [
        'page_id' => $pages[1]['page_id'],
        'identifier' => $pages[1]['identifier'],
        'label' => $pages[1]['title'],
        'request_url' => 'page-1/page-2',
        'level' => 2,
        'sort_order' => 5,
        'xpath' => "1/",
        'scope' => "store",
        'scope_id' => 1
    ],
];

foreach ($nodes as $node) {
    $nodeModel = $nodeFactory->create(['data' => $node]);

    if (isset($parentId)) {
        $nodeModel->setParentId($parentId);
    }

    $nodeModel = $nodeRepository->save($nodeModel);
    $parentId = $nodeModel->getId();
}
