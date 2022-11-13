<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Quote\Model\GetQuoteByReservedOrderId;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;
use Magento\Quote\Api\CartRepositoryInterface;

Resolver::getInstance()->requireDataFixture('Magento/Sales/_files/quote_with_customer.php');
Resolver::getInstance()->requireDataFixture('Magento/CustomerBalance/_files/customer_balance_default_website.php');

$objectManager = Bootstrap::getObjectManager();
/** @var GetQuoteByReservedOrderId $getQuoteByReservedOrderId */
$getQuoteByReservedOrderId = $objectManager->get(GetQuoteByReservedOrderId::class);
$quote = $getQuoteByReservedOrderId->execute('test01');
$quote->setUseCustomerBalance(true);

/** @var CartRepositoryInterface $quoteRepository */
$quoteRepository = $objectManager->get(CartRepositoryInterface::class);
$quoteRepository->save($quote->collectTotals());
