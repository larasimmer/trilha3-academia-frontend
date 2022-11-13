<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Eav\Model\Config;
use Magento\Catalog\Model\Product;

Resolver::getInstance()->requireDataFixture(
    'Magento/CustomerCustomAttributes/_files/customer_custom_attribute.php'
);
$objectManager = Bootstrap::getObjectManager();
$eavConfig = $objectManager->get(Config::class);
$select = $eavConfig->getAttribute('customer', 'test_select_code');
$eavConfig = $objectManager->get(Config::class);
$multiSelect = $eavConfig->getAttribute('customer', 'multi_select_attribute_code');
/** @var $entityType \Magento\Eav\Model\Entity\Type */
$entityType = $objectManager
    ->create(\Magento\Eav\Model\Config::class)
    ->getEntityType('customer');

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

$customer = $objectManager
    ->create(\Magento\Customer\Model\Customer::class);
$customer->setWebsiteId(1)
    ->setEntityId(1)
    ->setEntityTypeId($entityType->getId())
    ->setAttributeSetId($entityType->getDefaultAttributeSetId())
    ->setEmail('CharlesTAlston@teleworm.us')
    ->setPassword('password')
    ->setGroupId(1)
    ->setStoreId(1)
    ->setIsActive(1)
    ->setFirstname('Charles')
    ->setLastname('Alston')
    ->setGender(2)
    ->setTestSelectCode($selectOptions['Second'])
    ->setMultiSelectAttributeCode($multiSelectOptions['Option 1'] . ',' . $multiSelectOptions['Option 2']);
$customer->isObjectNew(true);


// Create address
$address = $objectManager->create(\Magento\Customer\Model\Address::class);
//  default_billing and default_shipping information would not be saved, it is needed only for simple check
$address->addData(
    [
        'firstname' => 'Charles',
        'lastname' => 'Alston',
        'street' => '3781 Neuport Lane',
        'city' => 'Panola',
        'country_id' => 'US',
        'region_id' => '51',
        'postcode' => '30058',
        'telephone' => '770-322-3514',
        'default_billing' => 1,
        'default_shipping' => 1,
    ]
);

// Assign customer and address
$customer->addAddress($address);
$customer->save();

// Mark last address as default billing and default shipping for current customer
$customer->setDefaultBilling($address->getId());
$customer->setDefaultShipping($address->getId());
$customer->save();

$objectManager->get(\Magento\Framework\Registry::class)->unregister('_fixture/Magento_ImportExport_Customer');
$objectManager->get(\Magento\Framework\Registry::class)->register('_fixture/Magento_ImportExport_Customer', $customer);
