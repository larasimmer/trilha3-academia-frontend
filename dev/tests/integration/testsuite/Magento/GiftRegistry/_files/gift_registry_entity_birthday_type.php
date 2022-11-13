<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Customer\Model\AddressFactory;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use Magento\GiftRegistry\Helper\Data;
use Magento\GiftRegistry\Model\EntityFactory;
use Magento\GiftRegistry\Model\PersonFactory;
use Magento\GiftRegistry\Model\ResourceModel\Entity as GiftRegistryResourceModel;
use Magento\GiftRegistry\Model\ResourceModel\Person as PersonResourceModel;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/Customer/_files/customer.php');

$objectManager = Bootstrap::getObjectManager();
/** @var StoreManagerInterface $storeManager */
$storeManager = $objectManager->get(StoreManagerInterface::class);
/** @var CustomerRegistry $customerRegistry */
$customerRegistry = $objectManager->get(CustomerRegistry::class);
$customer = $customerRegistry->retrieveByEmail(
    'customer@example.com',
    $storeManager->getWebsite('base')->getId()
);
/** @var EntityFactory $giftRegistryFactory */
$giftRegistryFactory = $objectManager->get(EntityFactory::class);
/** @var PersonFactory $personFactory */
$personFactory = $objectManager->get(PersonFactory::class);
/** @var AddressFactory $addressFactory */
$addressFactory = $objectManager->get(AddressFactory::class);
/** @var GiftRegistryResourceModel $giftRegistryResource */
$giftRegistryResource = $objectManager->get(GiftRegistryResourceModel::class);
/** @var PersonResourceModel $personResource */
$personResource = $objectManager->get(PersonResourceModel::class);
/** @var DateTimeFactory $dateFactory */
$dateFactory = $objectManager->get(DateTimeFactory::class);
$datetime = $dateFactory->create();
$giftRegistryHelper = $objectManager->get(Data::class);
$data = [
    'type_id' => '1',
    'title' => 'Gift Registry Birthday Type',
    'message' => 'Gift registry birthday type message',
    'is_public' => '1',
    'is_active' => '1',
    'event_country' => 'US',
    'event_country_region' => '43',
    'event_date' => $datetime->date('m/d/y', '+1 month'),
    'registrant' => [
        [
            'firstname' => 'Firstname1',
            'lastname' => 'Lastname1',
            'email' => 'gift.registrant1@magento.com',
        ],
        [
            'firstname' => 'Firstname2',
            'lastname' => 'Lastname2',
            'email' => 'gift.registrant2@magento.com',
        ],
    ],
    'address' => [
        'firstname' => $customer->getFirstname(),
        'lastname' => $customer->getLastname(),
        'company' => 'Company Name 111',
        'street' => [
            'First st. 444',
            'Second st. 555',
        ],
        'city' => 'New York',
        'region_id' => '43',
        'postcode' => '123456',
        'country_id' => 'US',
        'telephone' => '+14654568445',
    ],
];
$giftRegistry = $giftRegistryFactory->create();
$giftRegistry->setTypeById($data['type_id']);
$data = $giftRegistryHelper->filterDatesByFormat($data, $giftRegistry->getDateFieldArray());
$giftRegistry->importData($data, false)
    ->addData(
        [
            'customer_id' => $customer->getId(),
            'website_id' => $customer->getWebsiteId(),
            'url_key' => 'gift_registry_birthday_type_url',
            'created_at' => $datetime->date(),
            'is_add_action' => true,
        ]
    );
$persons = [];
foreach ($data['registrant'] as $registrant) {
    $person = $personFactory->create();
    $person->setData($registrant);
    $persons[] = $person;
}
$address = $addressFactory->create();
$address->setData($data['address']);
$giftRegistry->importAddress($address);

$giftRegistryResource->save($giftRegistry);

foreach ($persons as $person) {
    $person->setEntityId($giftRegistry->getId());
    $personResource->save($person);
}
