<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Rma\Api\RmaRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/Sales/_files/order_fixture_store_rollback.php');

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
