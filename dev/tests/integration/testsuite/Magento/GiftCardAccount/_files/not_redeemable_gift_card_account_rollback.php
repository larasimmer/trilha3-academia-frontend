<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\GiftCardAccount\Api\Data\GiftCardAccountInterface;
use Magento\GiftCardAccount\Api\GiftCardAccountRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
/** @var GiftCardAccountRepositoryInterface $giftCardAccountRepository */
$giftCardAccountRepository = $objectManager->get(GiftCardAccountRepositoryInterface::class);
/** @var GiftCardAccountInterface $giftCardAccount */
$giftCardAccount = $objectManager->get(GiftCardAccountInterface::class);

$giftCardAccount = $giftCardAccount->loadByCode('not_redeemable_gift_card_account');
if ($giftCardAccount->getId()) {
    $giftCardAccountRepository->delete($giftCardAccount);
}
