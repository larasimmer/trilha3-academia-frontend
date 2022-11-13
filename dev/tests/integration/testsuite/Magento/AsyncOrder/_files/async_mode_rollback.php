<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\TestFramework\ObjectManager;
use Magento\Framework\Config\File\ConfigFilePool;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Framework\App\DeploymentConfig\Writer;
use Magento\Framework\App\Config\ReinitableConfigInterface;

/** @var $objectManager ObjectManager */
$objectManager = Bootstrap::getObjectManager();

$configData = [
    'checkout' => [
        'async' => 0
    ]
];

/** @var Writer $config */
$config = $objectManager->get(Writer::class);
$config->saveConfig([ConfigFilePool::APP_ENV =>$configData]);
$objectManager->get(ReinitableConfigInterface::class)->reinit();
