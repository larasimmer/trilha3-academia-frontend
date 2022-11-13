<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\TestFramework\Helper\Bootstrap;
use Magento\Framework\App\ResourceConnection;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\Set;
use Magento\Customer\Model\Attribute;
use Magento\Customer\Api\AddressMetadataInterface;

$objectManager = Bootstrap::getObjectManager();

/**
 * This fixture is run outside of the transaction because it performs DDL operations during creating custom attribute.
 * All the changes are reverted in the appropriate "rollback" fixture.
 */
/** @var \Magento\TestFramework\Db\Adapter\TransactionInterface $connection */
$connection = $objectManager->get(ResourceConnection::class)
    ->getConnection('default');
$isTestDBIsolated = $connection->getTransactionLevel() > 0;

if ($isTestDBIsolated) {
    $connection->commitTransparentTransaction();
}

/** @var \Magento\Eav\Model\Entity\Type $entityType */
$entityType = $objectManager->create(Config::class)
    ->getEntityType(AddressMetadataInterface::ENTITY_TYPE_ADDRESS);

/** @var Set $attributeSet */
$attributeSet = $objectManager->create(Set::class);

$fileAttribute = $objectManager->create(
    Attribute::class,
    [
        'data' => [
            'frontend_input' => 'file',
            'frontend_label' => 'Document',
            'sort_order' => '0',
            'backend_type' => 'varchar',
            'is_user_defined' => 1,
            'is_system' => 0,
            'is_required' => '0',
            'is_visible' => '1',
            'attribute_set_id' => $entityType->getDefaultAttributeSetId(),
            'attribute_group_id' => $attributeSet->getDefaultGroupId($entityType->getDefaultAttributeSetId()),
            'entity_type_id' => $entityType->getId(),
            'default_value' => '',
            'used_in_forms' => ['customer_register_address', 'customer_address_edit', 'adminhtml_customer_address'],
        ]
    ]
);
$fileAttribute->setAttributeCode('document');
$fileAttribute->save();

if ($isTestDBIsolated) {
    $connection->beginTransparentTransaction();
}
