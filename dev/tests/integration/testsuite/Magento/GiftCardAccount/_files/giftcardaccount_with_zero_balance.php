<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/** @var $model \Magento\GiftCardAccount\Model\Giftcardaccount */
$model = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
    \Magento\GiftCardAccount\Model\Giftcardaccount::class
);
$model->setCode(
    'giftcardaccount_with_zero_balance'
)->setStatus(
    \Magento\GiftCardAccount\Model\Giftcardaccount::STATUS_ENABLED
)->setState(
    \Magento\GiftCardAccount\Model\Giftcardaccount::STATE_USED
)->setWebsiteId(
    \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(
        \Magento\Store\Model\StoreManagerInterface::class
    )->getWebsite()->getId()
)->setIsRedeemable(
    \Magento\GiftCardAccount\Model\Giftcardaccount::REDEEMABLE
)->setBalance(
    0.00
)->setDateExpires(
    date('Y-m-d', strtotime('+1 week'))
)->save();
