<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\CustomerBalance\Model\BalanceFactory;
use Magento\CustomerBalance\Model\ResourceModel\Balance as BalanceResource;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/Customer/_files/customer.php');

$objectManager = Bootstrap::getObjectManager();
/** @var CustomerRepositoryInterface $customerRepository */
$customerRepository = $objectManager->get(CustomerRepositoryInterface::class);
/** @var BalanceFactory $customerBalance */
$customerBalanceFactory = $objectManager->get(BalanceFactory::class);
/** @var BalanceResource $customerBalance */
$customerBalanceResource = $objectManager->get(BalanceResource::class);
$customer = $customerRepository->get('customer@example.com');

$customerBalance = $customerBalanceFactory->create();
$customerBalance->setCustomerId($customer->getId())
    ->setAmountDelta(50)
    ->setWebsiteId(null);
$customerBalanceResource->save($customerBalance);
