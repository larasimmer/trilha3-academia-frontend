<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Customer\Model\Attribute;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\Set;
use Magento\Eav\Model\Entity\Type;
use Magento\Framework\App\ResourceConnection;
use Magento\TestFramework\Db\Adapter\TransactionInterface;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * This fixture is run outside of the transaction because it performs DDL operations during creating custom attribute.
 * All the changes are reverted in the appropriate "rollback" fixture.
 */

/** @var $connection TransactionInterface */
$connection = Bootstrap::getObjectManager()
    ->get(ResourceConnection::class)
    ->getConnection('default');
$connection->commitTransparentTransaction();

/** @var $entityType Type */
$entityType = Bootstrap::getObjectManager()
    ->create(Config::class)
    ->getEntityType('customer_address');

/** @var $attributeSet Set */
$attributeSet = Bootstrap::getObjectManager()
    ->create(Set::class);

$multiSelect = Bootstrap::getObjectManager()->create(
    Attribute::class,
    [
        'data' => [
            'frontend_input' => 'multiselect',
            'frontend_label' => ['multi_select_attribute'],
            'sort_order' => '0',
            'backend_type' => 'varchar',
            'is_user_defined' => 1,
            'is_system' => 0,
            'is_required' => '0',
            'is_visible' => '1',
            'option' => [
                'value' => ['option_0' => ['Option 1'], 'option_1' => ['Option 2'], 'option_2' => ['Option 3']],
                'order' => ['option_0' => 1, 'option_1' => 2, 'option_2' => 3],
            ],
            'attribute_set_id' => $entityType->getDefaultAttributeSetId(),
            'attribute_group_id' => $attributeSet->getDefaultGroupId($entityType->getDefaultAttributeSetId()),
            'entity_type_id' => $entityType->getId(),
            'default_value' => '',
            'used_in_forms' => ['customer_register_address'],
            'source_model' => Magento\Eav\Model\Entity\Attribute\Source\Table::class,
        ]
    ]
);
$multiSelect->setAttributeCode('multi_select_attribute_code');
$multiSelect->save();

$connection->beginTransparentTransaction();
