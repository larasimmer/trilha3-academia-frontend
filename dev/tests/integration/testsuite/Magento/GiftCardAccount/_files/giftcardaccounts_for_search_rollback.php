<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);


$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

/** @var \Magento\GiftCardAccount\Api\GiftCardAccountRepositoryInterface $repo */
$repo = $objectManager->create(\Magento\GiftCardAccount\Api\GiftCardAccountRepositoryInterface::class);

/** @var \Magento\Framework\Api\SearchCriteriaBuilder $criteriaBuilder */
$criteriaBuilder = $objectManager->get(\Magento\Framework\Api\SearchCriteriaBuilder::class);
/** @var \Magento\Framework\Api\FilterBuilder $filterBuilder */
$filterBuilder = $objectManager->create(\Magento\Framework\Api\FilterBuilder::class);
$filter1 = $filterBuilder->setField('code')
    ->setValue('gift_card_account_1')
    ->create();
$filter2 = $filterBuilder->setField('code')
    ->setValue('gift_card_account_2')
    ->create();
$filter3 = $filterBuilder->setField('code')
    ->setValue('gift_card_account_3')
    ->create();
$filter4 = $filterBuilder->setField('code')
    ->setValue('gift_card_account_4')
    ->create();
$filter5 = $filterBuilder->setField('code')
    ->setValue('gift_card_account_5')
    ->create();
$filter6 = $filterBuilder->setField('code')
    ->setValue('gift_card_account_6')
    ->create();
$accounts = $repo->getList(
    $criteriaBuilder->addFilters(
        [
        $filter1, $filter2, $filter3, $filter4, $filter5, $filter6
        ]
    )->create()
)->getItems();
foreach ($accounts as $account) {
    $repo->delete($account);
}
