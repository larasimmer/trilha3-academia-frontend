<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Staging\Model\UpdateFactory;
use Magento\Staging\Model\UpdateRepository;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
$updateFactory = $objectManager->get(UpdateFactory::class);
$updateRepository = $objectManager->get(UpdateRepository::class);

$startTime = date('Y-m-d H:i:s', strtotime('+1 day'));
$updateData = [
    'name' => 'Product Update Test',
    'start_time' => $startTime,
    'end_time' => null,
    'is_campaign' => 0,
    'is_rollback' => null,
];

$update = $updateFactory->create(['data' => $updateData]);
$updateRepository->save($update);
