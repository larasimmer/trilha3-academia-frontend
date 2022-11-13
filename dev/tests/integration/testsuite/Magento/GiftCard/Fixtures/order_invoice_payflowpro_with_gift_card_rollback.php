<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\GiftCardAccount\Api\GiftCardAccountRepositoryInterface;

/** @var ObjectManager $objectManager */
$objectManager = Bootstrap::getObjectManager();
$incrementId = '100000002';

/** @var \Magento\Framework\Registry $registry */
$registry = $objectManager->get(\Magento\Framework\Registry::class);

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var FilterBuilder $filterBuilder */
$filterBuilder = $objectManager->get(FilterBuilder::class);
$filters = [
    $filterBuilder->setField(OrderInterface::INCREMENT_ID)
        ->setValue($incrementId)
        ->create()
];

/** @var SearchCriteriaBuilder $searchCriteriaBuilder */
$searchCriteriaBuilder = $objectManager->get(SearchCriteriaBuilder::class);
$searchCriteria = $searchCriteriaBuilder->addFilters($filters)
    ->create();

/** @var OrderRepositoryInterface $orderRepository */
$orderRepository = $objectManager->get(OrderRepositoryInterface::class);
$items = $orderRepository->getList($searchCriteria)
    ->getItems();

foreach ($items as $item) {
    try {
        $orderRepository->delete($item);
    } catch (\Exception $e) {
    }
}

$filters = [
    $filterBuilder->setField('code')
        ->setConditionType('in')
        ->setValue(['fixture_code_1', 'fixture_code_2', 'fixture_code_3'])
        ->create()
];

/** @var GiftCardAccountRepositoryInterface $repo */
$giftAccountRepo = $objectManager->create(GiftCardAccountRepositoryInterface::class);
$searchCriteria = $searchCriteriaBuilder->addFilters($filters)
    ->create();
$items = $giftAccountRepo->getList($searchCriteria)
    ->getItems();

foreach ($items as $item) {
    try {
        $giftAccountRepo->delete($item);
    } catch (CouldNotDeleteException $e) {
    }
}

$deleteConfigData = function (WriterInterface $writer, $scope, $scopeId) {
    $configData = [
        'payment/payflowpro/active',
        'payment/payflowpro_cc_vault/active',
        'payment/payflowpro/merchant_id',
        'payment/payflowpro/public_key',
        'payment/payflowpro/private_key',
        'payment/payflowpro/payment_action'
    ];
    foreach ($configData as $path) {
        try {
            $writer->delete($path, $scope, $scopeId);
        } catch (NoSuchEntityException $e) {
        }
    }
};

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
