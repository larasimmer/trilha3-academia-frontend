<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/Sales/_files/quote_with_customer.php');
$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
$configResource = $objectManager->get(\Magento\Config\Model\ResourceModel\Config::class);
$configResource->saveConfig(
    'checkout/deferred_total_calculating',
    '1',
    'default',
    0
);
/** @var \Magento\Framework\App\Config\ReinitableConfigInterface $config */
$config = $objectManager->get(\Magento\Framework\App\Config\ReinitableConfigInterface::class);
$config->reinit();
