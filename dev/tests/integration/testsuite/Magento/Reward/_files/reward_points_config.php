<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
/** @var MutableScopeConfigInterface $mutableConfig */
$mutableConfig = $objectManager->get(MutableScopeConfigInterface::class);
$mutableConfig->setValue('magento_reward/points/invitation_customer', '10', ScopeInterface::SCOPE_WEBSITE, 'base');
$mutableConfig->setValue('magento_reward/points/invitation_order', '5', ScopeInterface::SCOPE_WEBSITE, 'base');
