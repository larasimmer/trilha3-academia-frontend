<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\TestFramework\Helper\Bootstrap;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\Attribute\Set as AttributeSet;
use Magento\Customer\Model\Attribute as CustomerAttribute;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Model\Customer;
use Magento\Framework\Indexer\IndexerRegistry;

$objectManager = Bootstrap::getObjectManager();

/**
 * # Create Customer Custom Address Attribute that is used in grid.
 */
/** @var \Magento\Eav\Model\Entity\Type $entityType */
$entityType = $objectManager->create(EavConfig::class)
    ->getEntityType('customer_address');
/** @var AttributeSet $attributeSet */
$attributeSet = $objectManager->create(AttributeSet::class);
/** @var CustomerAttribute $customAttribute */
$customAttribute = $objectManager->create(
    CustomerAttribute::class,
    [
        'data' => [
            'frontend_input' => 'text',
            'frontend_label' => ['Customer Code'],
            'sort_order' => 1,
            'backend_type' => 'varchar',
            'is_user_defined' => 1,
            'is_system' => 0,
            'is_used_in_grid' => 1,
            'is_required' => '0',
            'is_visible' => 1,
            'used_in_forms' => [
                'customer_address_edit',
                'adminhtml_customer_address'
            ],
            'attribute_set_id' => $entityType->getDefaultAttributeSetId(),
            'attribute_group_id' => $attributeSet->getDefaultGroupId($entityType->getDefaultAttributeSetId()),
            'entity_type_id' => $entityType->getId(),
            'default_value' => '',
        ],
    ]
);
$customAttribute->setAttributeCode('customer_code');
/** @var AttributeRepositoryInterface $eavRepository */
$eavRepository = $objectManager->get(AttributeRepositoryInterface::class);
$eavRepository->save($customAttribute);

/**
 * # Create Customer.
 */
/** @var CustomerInterface $customer */
$customer = $objectManager->create(CustomerInterface::class);
$customer->setWebsiteId(0)
    ->setEmail('addressattribute@visibilityongrid.com')
    ->setGroupId(0)
    ->setStoreId(0)
    ->setFirstname('Betsy')
    ->setLastname('Parker')
    ->setGender(2);
/** @var CustomerRepositoryInterface $customerRepository */
$customerRepository = $objectManager->get(CustomerRepositoryInterface::class);
$customerRepository->save($customer);
$customer = $customerRepository->get('addressattribute@visibilityongrid.com', 0);

/**
 * # Create Address
 */
/** @var AddressInterface $address */
$address = $objectManager->create(AddressInterface::class);
$address->setFirstname('Betsy');
$address->setLastname('Parker');
$address->setStreet(['1079 Rocky Road']);
$address->setCity('Philadelphia');
$address->setCountryId('US');
$address->setRegionId(51);
$address->setPostcode('19107');
$address->setTelephone('215-629-9720');
$address->setCustomAttribute('customer_code', '123q');
$address->setIsDefaultShipping(true);
$address->setIsDefaultBilling(true);
$address->setCustomerId($customer->getId());

/** @var AddressRepositoryInterface $addressRepository */
$addressRepository = $objectManager->get(AddressRepositoryInterface::class);
$addressRepository->save($address);

/**
 * # Reindex Customer Grid
 */
Bootstrap::getInstance()->reinitialize();
/** @var IndexerRegistry $indexerRegistry */
$indexerRegistry = $objectManager->create(IndexerRegistry::class);
/** @var \Magento\Framework\Indexer\IndexerInterface $indexer */
$indexer = $indexerRegistry->get(Customer::CUSTOMER_GRID_INDEXER_ID);
$indexer->reindexAll();
