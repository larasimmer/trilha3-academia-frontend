<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

$giftCardCode = 'giftcardaccount_with_past_date_expires';
Resolver::getInstance()->requireDataFixture('Magento/GiftCardAccount/_files/giftcardaccount_rollback.php');
