<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\VersionsCms\Model\Hierarchy\Node;

$objectManager = Bootstrap::getObjectManager();
/** @var PageRepositoryInterface $pageRepository */
$pageRepository = $objectManager->get(PageRepositoryInterface::class);
/** @var SearchCriteriaBuilder $searchCriteriaBuilder */
$searchCriteriaBuilder = $objectManager->create(SearchCriteriaBuilder::class);

$pageIdentifiers = ['test-page'];
$scope = 'default';
$scopeId = 0;

$searchCriteria = $searchCriteriaBuilder->addFilter(
    'main_table.' . PageInterface::IDENTIFIER,
    $pageIdentifiers,
    'in'
)->create();

$result = $pageRepository->getList($searchCriteria);

foreach ($result->getItems() as $page) {
    $pageRepository->delete($page);
}

$node = $objectManager->get(Node::class);
$node->setScope($scope);
$node->setScopeId($scopeId);
$node->deleteByScope($scope, $scopeId);
$node->collectTree([], []);
