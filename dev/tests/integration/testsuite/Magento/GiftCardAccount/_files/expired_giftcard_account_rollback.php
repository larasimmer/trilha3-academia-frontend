<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

$giftCardCode = 'expired_giftcard_account';
Resolver::getInstance()->requireDataFixture('Magento/GiftCardAccount/_files/giftcardaccount_rollback.php');
