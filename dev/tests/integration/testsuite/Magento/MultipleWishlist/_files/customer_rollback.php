<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

$registry = $objectManager->get(\Magento\Framework\Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var $customer \Magento\Customer\Model\Customer*/
$customer =$objectManager->create(\Magento\Customer\Model\Customer::class);
$customer->load(1);
if ($customer->getId()) {
    $customer->delete();
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);

/* Unlock account if it was locked for tokens retrieval */
$throttler = $objectManager->create(\Magento\Integration\Model\Oauth\Token\RequestThrottler::class);
$throttler->resetAuthenticationFailuresCount(
    'customer@example.com',
    \Magento\Integration\Model\Oauth\Token\RequestThrottler::USER_TYPE_CUSTOMER
);
