<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\TestFramework\Helper\Bootstrap;

/** @var Value $configValue */
$configValue = Bootstrap::getObjectManager()->create(Value::class);
/** @var \Magento\Config\Model\ResourceModel\Config\Data $configResource */
$configResource = Bootstrap::getObjectManager()->create(\Magento\Config\Model\ResourceModel\Config\Data::class);
$configResource->load($configValue, 'catalog/magento_catalogpermissions/enabled', 'path');
$configValue->setPath('catalog/magento_catalogpermissions/enabled');
$configValue->setScope('default');
$configValue->setScopeId(0);
$configValue->setValue(1);
$configResource->save($configValue);

/** @var ReinitableConfigInterface $reinitableConfig */
$reinitableConfig = Bootstrap::getObjectManager()->get(ReinitableConfigInterface::class);
$reinitableConfig->reinit();
