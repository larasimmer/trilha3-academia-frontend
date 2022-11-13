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
$dataToCurrency = $rateResource->getRateData(1, 1, Rate::RATE_EXCHANGE_DIRECTION_TO_CURRENCY);
if ($dataToCurrency && $dataToCurrency['rate_id']) {
    /** @var Rate $rate */
    $rate = $objectManager->get(RateFactory::class)->create(['data' => $dataToCurrency]);
    $rateResource->delete($rate);
}
$dataToPoints = $rateResource->getRateData(1, 1, Rate::RATE_EXCHANGE_DIRECTION_TO_POINTS);
if ($dataToPoints && $dataToPoints['rate_id']) {
    /** @var Rate $rate */
    $rate = $objectManager->get(RateFactory::class)->create(['data' => $dataToPoints]);
    $rateResource->delete($rate);
}
