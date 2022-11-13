<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Reward\Model\ResourceModel\Reward\Rate as RateResource;
use Magento\Reward\Model\Reward\Rate;
use Magento\Reward\Model\Reward\RateFactory;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
/** @var RateResource $rateResource */
$rateResource = $objectManager->get(RateResource::class);
$data = $rateResource->getRateData(0, 0, Rate::RATE_EXCHANGE_DIRECTION_TO_POINTS);
if ($data && $data['rate_id']) {
    /** @var Rate $rate */
    $rate = $objectManager->get(RateFactory::class)->create(['data' => $data]);
    $rateResource->delete($rate);
}
