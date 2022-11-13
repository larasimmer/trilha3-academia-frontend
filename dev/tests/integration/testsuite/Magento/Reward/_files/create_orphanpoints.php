<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Customer\Model\Customer;
use Magento\Store\Api\WebsiteRepositoryInterface;
use \Magento\TestFramework\Helper\Bootstrap;
use \Magento\Store\Model\Website;
use \Magento\Reward\Model\Reward;
use \Magento\Framework\Registry;

$objectManager=Bootstrap::getObjectManager();

$registry = $objectManager->get(Registry::class);

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var $website \Magento\Store\Model\Website */
$website = $objectManager->create(Website::class);
$website->setName('Second Website')->setCode('secondwebsite')->save();
$websiteId= $website->getId();

/** @var $customer \Magento\Customer\Model\Customer */
$customer = $objectManager->create(Customer::class);
$customer->setWebsiteId($websiteId)
    ->setEmail('company_related@company.com')
    ->setPassword('password')
    ->setFirstname('John')
    ->setLastname('Smith');
$customer->isObjectNew(true);
$customer->save();
$customerId= $customer->getId();

/** @var $reward \Magento\Reward\Model\Reward */
$reward = $objectManager->create(Reward::class);
$reward->setCustomerId($customerId)->setWebsiteId($websiteId);
$reward->setPointsBalance(1000);
$reward->save();

$websiteRepository = $objectManager->get(WebsiteRepositoryInterface::class);
$website = $websiteRepository->getById($websiteId);
// delete website to create orphan points
if ($website->getId()) {
    $website->delete();
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);

return $customer;
