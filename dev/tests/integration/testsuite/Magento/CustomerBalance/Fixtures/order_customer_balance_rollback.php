<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

$objectManager = Bootstrap::getObjectManager();

/** @var SearchCriteriaBuilder $searchCriteriaBuilder */
$searchCriteriaBuilder = $objectManager->get(SearchCriteriaBuilder::class);
$searchCriteria = $searchCriteriaBuilder->addFilter('increment_id', '100000002')
    ->create();

/** @var OrderRepositoryInterface $repository */
$repository = $objectManager->get(OrderRepositoryInterface::class);
$items = $repository->getList($searchCriteria)
    ->getItems();

foreach ($items as $item) {
    $repository->delete($item);
}

Resolver::getInstance()->requireDataFixture('Magento/Catalog/_files/product_simple_rollback.php');
Resolver::getInstance()->requireDataFixture('Magento/Customer/_files/customer_rollback.php');
