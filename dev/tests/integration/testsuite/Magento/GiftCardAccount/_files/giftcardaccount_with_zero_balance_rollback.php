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
$accounts = $repo->getList(
    $criteriaBuilder->addFilter('code', 'giftcardaccount_with_zero_balance')->setPageSize(1)->create()
)->getItems();
$account = array_pop($accounts);
if ($account) {
    $repo->delete($account);
}
