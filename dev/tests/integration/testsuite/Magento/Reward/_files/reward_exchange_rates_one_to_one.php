<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Customer\Model\Group;
use Magento\Reward\Model\ResourceModel\Reward\Rate as RateResource;
use Magento\Reward\Model\Reward\Rate;
use Magento\Reward\Model\Reward\RateFactory;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
/** @var RateFactory $rateFactory */
$rateFactory = $objectManager->get(RateFactory::class);
/** @var RateResource $rateResource */
$rateResource = $objectManager->get(RateResource::class);
/** @var WebsiteRepositoryInterface $websiteRepository */
$websiteRepository = $objectManager->get(WebsiteRepositoryInterface::class);
$defaultWebsiteId = $websiteRepository->get('base')->getId();

foreach ([Rate::RATE_EXCHANGE_DIRECTION_TO_CURRENCY, Rate::RATE_EXCHANGE_DIRECTION_TO_POINTS] as $direction) {
    /** @var Rate $rate */
    $rate = $rateFactory->create();
    $rate->addData(
        [
            'website_id' => $defaultWebsiteId,
            'customer_group_id' => Group::NOT_LOGGED_IN_ID,
            'direction' => $direction,
            'value' => 1,
            'equal_value' => 1,
        ]
    );
    $rateResource->save($rate);
}
