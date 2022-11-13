<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
use Magento\Customer\Model\Customer;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

/** @var \Magento\Framework\Registry $registry */
$registry = Bootstrap::getObjectManager()->get(\Magento\Framework\Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var Customer $customer */
$customer = Bootstrap::getObjectManager()
    ->create(Customer::class);
$customer->setWebsiteId(1);
$customer->loadByEmail('JohnDoe@mail.com');
$customer->delete();

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);

Resolver::getInstance()->requireDataFixture(
    'Magento/CustomerCustomAttributes/_files/customer_custom_text_attribute_rollback.php'
);
