<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

/** @var \Magento\Framework\Registry $registry */
$registry = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(\Magento\Framework\Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var \Magento\Customer\Model\Customer $customer */
$customer = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
    ->create(\Magento\Customer\Model\Customer::class);
$customer->setWebsiteId(0);
$customer->loadByEmail('BetsyParker@example.com');
$customer->delete();

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);


Resolver::getInstance()->requireDataFixture(
    'Magento/CustomerCustomAttributes/_files/address_custom_attribute_rollback.php'
);
