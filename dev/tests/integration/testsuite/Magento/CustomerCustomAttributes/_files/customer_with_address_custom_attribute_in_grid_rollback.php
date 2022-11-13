<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\TestFramework\Helper\Bootstrap;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Customer\Model\Indexer\Address\AttributeProvider as AddressAttributeProvider;

$objectManager = Bootstrap::getObjectManager();

/**
 * # Delete Customer
 */
/** @var CustomerRepositoryInterface $customerRepository */
$customerRepository = $objectManager->create(CustomerRepositoryInterface::class);
$customer = $customerRepository->get('addressattribute@visibilityongrid.com', 0);
$customerRepository->delete($customer);

/**
 * # Delete Attribute
 */
/** @var AttributeRepositoryInterface $eavRepository */
$eavRepository = $objectManager->get(AttributeRepositoryInterface::class);
$attirbute = $eavRepository->get(AddressAttributeProvider::ENTITY, 'customer_code');
$eavRepository->delete($attirbute);
