<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Customer\Model\Customer;
use \Magento\TestFramework\Helper\Bootstrap;
use Magento\Reward\Model\Reward;
use Magento\Quote\Model\Quote;
use Magento\Reward\Model\Reward\Rate;

$objectManager=Bootstrap::getObjectManager();

/** @var $customer Customer */
$customer = $objectManager->create(Customer::class);
$customer->setWebsiteId(1)
    ->setEmail('john_smith@company.com')
    ->setPassword('password')
    ->setFirstname('John')
    ->setLastname('Smith');
$customer->isObjectNew(true);
$customer->save();
$customerId= $customer->getId();

$exchangeToCurrency = [
    'website_id' => 1,
    'customer_group_id' => 0,
    'direction' => 1,
    'value' => 1000,
    'equal_value' => 5
];

$exchangeToPoints = [
    'website_id' => 1,
    'customer_group_id' => 0,
    'direction' => 2,
    'value' => 100,
    'equal_value' => 1,
];

/** @var Rate $rate */
$rate = $objectManager->create(Rate::class);
$rate->addData($exchangeToCurrency);
$rate->save();

$rate = $objectManager->create(Rate::class);
$rate->addData($exchangeToPoints);
$rate->save();

/** @var $reward Reward */
$reward = $objectManager->create(Reward::class);
$reward->setCustomerId($customerId)->setWebsiteId(1);
$reward->setPointsBalance(1000);
$reward->save();



/** @var $quote Quote */
$quote = $objectManager->create(Quote::class);
$quote->setCustomerId($customerId);
$quote->setStoreId(1);
$quote->setUseRewardPoints(true);
$quote->collectTotals();
$quote->save();

return $quote;
