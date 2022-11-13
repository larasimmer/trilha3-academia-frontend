<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Rma\Api\RmaRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/Sales/_files/order_list_rollback.php');
Resolver::getInstance()->requireDataFixture('Magento/Catalog/_files/product_simple_rollback.php');

/** @var RmaRepositoryInterface $rmaRepository */
$rmaRepository = Bootstrap::getObjectManager()->get(RmaRepositoryInterface::class);

/** @var SearchCriteriaBuilder $searchCriteriaBuilder */
$searchCriteriaBuilder = Bootstrap::getObjectManager()->get(SearchCriteriaBuilder::class);
$searchCriteria = $searchCriteriaBuilder->addFilter('is_active', 1)->create();

$items = $rmaRepository->getList($searchCriteria)
    ->getItems();

foreach ($items as $item) {
    $rmaRepository->delete($item);
}
