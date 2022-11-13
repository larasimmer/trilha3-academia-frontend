<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Eav\Model\Config;

Resolver::getInstance()->requireDataFixture('Magento/CustomerCustomAttributes/_files/address_custom_attribute.php');
/** @var Config $eavConfig */
$eavConfig = Bootstrap::getObjectManager()->get(Config::class);
$select = $eavConfig->getAttribute('customer_address', 'test_select_code');
$eavConfig = Bootstrap::getObjectManager()->get(Config::class);
$multiSelect = $eavConfig->getAttribute('customer_address', 'multi_select_attribute_code');
//Create customer
$customer = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
    ->create(\Magento\Customer\Model\Customer::class);
$customer->setWebsiteId(0)
    ->setEntityId(1)
    ->setEntityTypeId(1)
    ->setAttributeSetId(0)
    ->setEmail('BetsyParker@example.com')
    ->setPassword('password')
    ->setGroupId(0)
    ->setStoreId(0)
    ->setIsActive(1)
    ->setFirstname('Betsy')
    ->setLastname('Parker')
    ->setGender(2);
$customer->isObjectNew(true);
$customer->save();

$selectOptions = [];
foreach ($select->getOptions() as $option) {
    if ($option->getValue()) {
        $selectOptions[$option->getLabel()] = $option->getValue();
    }
}

$multiSelectOptions = [];
foreach ($multiSelect->getOptions() as $option) {
    if ($option->getValue()) {
        $multiSelectOptions[$option->getLabel()] = $option->getValue();
    }
}

// Create and set addresses
$addressFirst = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
    ->create(\Magento\Customer\Model\Address::class);
$addressFirst->addData(
    [
        'entity_id' => 1,
        'firstname' => 'Betsy',
        'lastname' => 'Parker',
        'street' => '1079 Rocky Road',
        'city' => 'Philadelphia',
        'country_id' => 'US',
        'region_id' => '51',
        'postcode' => '19107',
        'telephone' => '215-629-9720',
        'test_select_code' => $selectOptions['Second'],
        'multi_select_attribute_code' => $multiSelectOptions['Option 2'],
    ]
);
$addressFirst->isObjectNew(true);
$customer->addAddress($addressFirst);
$customer->setDefaultBilling($addressFirst->getId());

$addressSecond = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
    ->create(\Magento\Customer\Model\Address::class);
$addressSecond->addData(
    [
        'entity_id' => 2,
        'firstname' => 'Anthony',
        'lastname' => 'Nealy',
        'street' => '3176 Cambridge Court',
        'city' => 'Fayetteville',
        'country_id' => 'US',
        'region_id' => '5',
        'postcode' => '72701',
        'telephone' => '479-899-9849',
        'test_select_code' => $selectOptions['First'],
        'multi_select_attribute_code' => $multiSelectOptions['Option 1'] . ',' . $multiSelectOptions['Option 3'],
    ]
);
$addressSecond->isObjectNew(true);
$customer->addAddress($addressSecond);
$customer->setDefaultShipping($addressSecond->getId());
$customer->isObjectNew(true);
$customer->save();
