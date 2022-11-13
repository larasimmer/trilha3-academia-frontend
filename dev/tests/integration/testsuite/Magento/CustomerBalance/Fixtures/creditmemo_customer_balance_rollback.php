<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

/** @var ObjectManager $objectManager */
$objectManager = Bootstrap::getObjectManager();

/** @var SearchCriteriaBuilder $searchCriteriaBuilder */
$searchCriteriaBuilder = $objectManager->get(SearchCriteriaBuilder::class);
$searchCriteria = $searchCriteriaBuilder->addFilter('increment_id', '100000002')
    ->create();

/** @var CreditmemoRepositoryInterface $repository */
$repository = $objectManager->get(CreditmemoRepositoryInterface::class);
$items = $repository->getList($searchCriteria)
    ->getItems();

foreach ($items as $item) {
    $repository->delete($item);
}

Resolver::getInstance()->requireDataFixture('Magento/CustomerBalance/Fixtures/order_customer_balance_rollback.php');
