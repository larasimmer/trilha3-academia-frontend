<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\GiftCardAccount\Api\GiftCardAccountRepositoryInterface;
use Magento\GiftCardAccount\Model\Giftcardaccount;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture(
    'Magento/GiftCardAccount/_files/creditmemo_with_gift_card_account.php'
);
$objectManager = Bootstrap::getObjectManager();
$giftcardaccountRepository = $objectManager->get(GiftCardAccountRepositoryInterface::class);

$giftcardAccount = $objectManager->create(Giftcardaccount::class);
$giftcardAccount2 = $objectManager->create(Giftcardaccount::class);

$giftcardAccount->loadByCode('TESTCODE1');
$giftcardAccount2->loadByCode('TESTCODE2');

$giftcardaccountRepository->delete($giftcardAccount);
$giftcardaccountRepository->delete($giftcardAccount2);
