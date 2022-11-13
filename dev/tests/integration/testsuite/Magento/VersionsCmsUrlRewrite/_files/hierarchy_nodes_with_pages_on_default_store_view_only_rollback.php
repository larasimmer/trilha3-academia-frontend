<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Api\Data\PageInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
/** @var PageRepositoryInterface $pageRepository */
$pageRepository = $objectManager->get(PageRepositoryInterface::class);
/** @var SearchCriteriaBuilder $searchCriteriaBuilder */
$searchCriteriaBuilder = $objectManager->create(SearchCriteriaBuilder::class);

$pageIdentifiers = ['page-1', 'page-2'];

$searchCriteria = $searchCriteriaBuilder->addFilter(
    'main_table.' . PageInterface::IDENTIFIER,
    $pageIdentifiers,
    'in'
)->create();

$result = $pageRepository->getList($searchCriteria);

foreach ($result->getItems() as $page) {
    $pageRepository->delete($page);
}
