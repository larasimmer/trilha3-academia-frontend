<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Reward\Model\ResourceModel\Reward as RewardResource;
use Magento\Reward\Model\Reward;
use Magento\Reward\Model\RewardFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/Customer/_files/customer.php');

$objectManager = Bootstrap::getObjectManager();
/** @var CustomerInterface $customer */
$customer = $objectManager->get(CustomerRepositoryInterface::class)->get('customer@example.com');
/** @var RewardResource $rewardResource */
$rewardResource = $objectManager->get(RewardResource::class);
/** @var Reward $reward */
$reward = $objectManager->get(RewardFactory::class)->create();
$reward->setCustomerId($customer->getId())
    ->setWebsiteId($customer->getWebsiteId())
    ->setCustomerGroupId($customer->getGroupId())
    ->setPointsDelta(5)
    ->setComment('Reward Comment')
    ->setAction(Reward::REWARD_ACTION_ADMIN);
$rewardResource->save($reward);
