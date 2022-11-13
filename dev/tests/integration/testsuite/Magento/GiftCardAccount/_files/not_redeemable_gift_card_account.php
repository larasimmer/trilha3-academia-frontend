<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\GiftCardAccount\Api\Data\GiftCardAccountInterfaceFactory;
use Magento\GiftCardAccount\Api\GiftCardAccountRepositoryInterface;
use Magento\GiftCardAccount\Model\Giftcardaccount;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
/** @var GiftCardAccountInterfaceFactory $giftCardAccountFactory */
$giftCardAccountFactory = $objectManager->get(GiftCardAccountInterfaceFactory::class);
/** @var GiftCardAccountRepositoryInterface $giftCardAccountRepository */
$giftCardAccountRepository = $objectManager->get(GiftCardAccountRepositoryInterface::class);
$websiteId = $objectManager->get(StoreManagerInterface::class)->getWebsite('base')->getId();

$giftCardAccount = $giftCardAccountFactory->create();
$giftCardAccount->setCode('not_redeemable_gift_card_account')
    ->setStatus(Giftcardaccount::STATUS_ENABLED)
    ->setState(Giftcardaccount::STATE_AVAILABLE)
    ->setWebsiteId($websiteId)
    ->setIsRedeemable(Giftcardaccount::NOT_REDEEMABLE)
    ->setBalance(25);
$giftCardAccountRepository->save($giftCardAccount);
