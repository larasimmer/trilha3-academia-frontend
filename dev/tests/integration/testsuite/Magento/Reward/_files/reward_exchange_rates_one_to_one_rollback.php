<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Customer\Model\Group;
use Magento\Reward\Model\ResourceModel\Reward\Rate as RateResource;
use Magento\Reward\Model\ResourceModel\Reward\Rate\Collection;
use Magento\Reward\Model\ResourceModel\Reward\Rate\CollectionFactory;
use Magento\Reward\Model\Reward\Rate;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
/** @var RateResource $rateResource */
$rateResource = $objectManager->get(RateResource::class);
/** @var WebsiteRepositoryInterface $websiteRepository */
$websiteRepository = $objectManager->get(WebsiteRepositoryInterface::class);
$defaultWebsiteId = $websiteRepository->get('base')->getId();
$directions = [Rate::RATE_EXCHANGE_DIRECTION_TO_CURRENCY, Rate::RATE_EXCHANGE_DIRECTION_TO_POINTS];
/** @var Collection $rateCollection */
$rateCollection = $objectManager->get(CollectionFactory::class)->create();
$rateCollection->addFieldToFilter('website_id', $defaultWebsiteId)
    ->addFieldToFilter('customer_group_id', Group::NOT_LOGGED_IN_ID)
    ->addFieldToFilter('direction', ['in' => $directions])
    ->load();

foreach ($rateCollection as $rate) {
    $rateResource->delete($rate);
}
