<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\GiftCardAccount\Model\Giftcardaccount;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

$giftCardCode = 'giftcardaccount_with_past_date_expires';
/** @var $model \Magento\GiftCardAccount\Model\Giftcardaccount */
$model = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
    \Magento\GiftCardAccount\Model\Giftcardaccount::class
);
$model->setCode(
    $giftCardCode ?? 'giftcardaccount_fixture'
)->setStatus(
    \Magento\GiftCardAccount\Model\Giftcardaccount::STATUS_ENABLED
)->setState(
    \Magento\GiftCardAccount\Model\Giftcardaccount::STATE_AVAILABLE
)->setWebsiteId(
    \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(
        \Magento\Store\Model\StoreManagerInterface::class
    )->getWebsite()->getId()
)->setIsRedeemable(
    \Magento\GiftCardAccount\Model\Giftcardaccount::REDEEMABLE
)->setBalance(
    9.99
)->setDateExpires(
    date('Y-m-d', strtotime('+1 week'))
)->save();

$objectManager = Bootstrap::getObjectManager();
/** @var $model Giftcardaccount */
/** @var \Magento\GiftCardAccount\Model\ResourceModel\Giftcardaccount $resourceModel */
$resourceModel = $objectManager->get(
    \Magento\GiftCardAccount\Model\ResourceModel\Giftcardaccount::class
);
$resourceModel->getConnection()->update(
    $resourceModel->getMainTable(),
    [
        'date_expires' => date('Y-m-d', strtotime('-2 day')),
    ],
    [
        'code=?' => $giftCardCode
    ]
);
