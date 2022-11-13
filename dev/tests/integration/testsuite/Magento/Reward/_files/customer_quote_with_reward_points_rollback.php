<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Quote\Api\CartRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Quote\Model\GetQuoteByReservedOrderId;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

$objectManager = Bootstrap::getObjectManager();
/** @var CartRepositoryInterface $quoteRepository */
$quoteRepository = $objectManager->get(CartRepositoryInterface::class);
/** @var GetQuoteByReservedOrderId $getQuoteByReservedOrderId */
$getQuoteByReservedOrderId = $objectManager->get(GetQuoteByReservedOrderId::class);
$quote = $getQuoteByReservedOrderId->execute('55555555');
if ($quote) {
    $quoteRepository->delete($quote);
}

Resolver::getInstance()->requireDataFixture('Magento/Catalog/_files/product_simple_duplicated_rollback.php');
Resolver::getInstance()->requireDataFixture('Magento/Customer/_files/customer_address_rollback.php');
Resolver::getInstance()->requireDataFixture('Magento/Reward/_files/customer_with_five_reward_points_rollback.php');
Resolver::getInstance()->requireDataFixture('Magento/Reward/_files/reward_exchange_rates_one_to_one_rollback.php');
