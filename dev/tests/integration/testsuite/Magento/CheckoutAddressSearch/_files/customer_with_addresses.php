<?php
/**
 * Customer address fixture with entity_id = 1
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Customer\Model\CustomerRegistry;

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
/** @var $customerRepository \Magento\Customer\Api\CustomerRepositoryInterface */
$customerRepository = $objectManager->create(\Magento\Customer\Api\CustomerRepositoryInterface::class);
$customer = $objectManager->create(\Magento\Customer\Model\Customer::class);
/** @var CustomerRegistry $customerRegistry */
$customerRegistry = $objectManager->get(CustomerRegistry::class);
/** @var Magento\Customer\Model\Customer $customer */
$customer->setWebsiteId(1)
    ->setId(1)
    ->setEmail('customer@example.com')
    ->setPassword('password')
    ->setGroupId(1)
    ->setStoreId(1)
    ->setIsActive(1)
    ->setPrefix('Mr.')
    ->setFirstname('John')
    ->setMiddlename('A')
    ->setLastname('Smith')
    ->setSuffix('Esq.')
    ->setDefaultBilling(1)
    ->setDefaultShipping(1)
    ->setTaxvat('12')
    ->setGender(0);

$customer->isObjectNew(true);
$customer->save();
$customerRegistry->remove($customer->getId());


/** @var \Magento\Customer\Model\CustomerRegistry $customerRegistry */
$customerRegistry = $objectManager->get(\Magento\Customer\Model\CustomerRegistry::class);

/** @var \Magento\Customer\Api\AddressRepositoryInterface $addressRepository */
$addressRepository = $objectManager->get(\Magento\Customer\Api\AddressRepositoryInterface::class);

/** @var \Magento\Customer\Model\Address $customerAddress */
$customerAddress = $objectManager->create(\Magento\Customer\Model\Address::class);
$customerAddress->isObjectNew(true);
$customerAddress->setData(
    [
        'entity_id' => 126292,
        'attribute_set_id' => 2,
        'telephone' => 3468676,
        'postcode' => 75477,
        'country_id' => 'US',
        'city' => 'San Francisco',
        'company' => 'CompanyName',
        'street' => 'Green str, 67',
        'lastname' => 'Smith',
        'firstname' => 'John',
        'parent_id' => 1,
        'region_id' => 12,
    ]
);
$customerAddress->save();
$customerAddress = $addressRepository->getById(126292);
$customerAddress->setCustomerId(1);
$customerAddress = $addressRepository->save($customerAddress);

/** @var \Magento\Customer\Model\Address $customerAddress */
$customerAddress = $objectManager->create(\Magento\Customer\Model\Address::class);
$customerAddress->isObjectNew(true);
$customerAddress->setData(
    [
        'entity_id' => 72534,
        'attribute_set_id' => 2,
        'telephone' => 3468676,
        'postcode' => 78757,
        'country_id' => 'US',
        'city' => 'San Antonio',
        'company' => 'Pumpkin & Sons',
        'street' => '35812 West Cucumber Lane',
        'lastname' => 'Smith',
        'firstname' => 'John',
        'parent_id' => 1,
        'region_id' => 57,
    ]
);
$customerAddress->save();

$customerAddress = $addressRepository->getById(72534);
$customerAddress->setCustomerId(1);
$customerAddress = $addressRepository->save($customerAddress);

$customerRegistry->remove($customerAddress->getCustomerId());
/** @var \Magento\Customer\Model\AddressRegistry $addressRegistry */
$addressRegistry = $objectManager->get(\Magento\Customer\Model\AddressRegistry::class);
$addressRegistry->remove($customerAddress->getId());
