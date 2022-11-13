<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Customer\Model\Attribute;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\Set;
use Magento\Eav\Model\Entity\Type;
use Magento\TestFramework\Helper\Bootstrap;

/** @var $entityType Type */
$entityType = Bootstrap::getObjectManager()
    ->create(Config::class)
    ->getEntityType('customer');

/** @var $attributeSet Set */
$attributeSet = Bootstrap::getObjectManager()
    ->create(Set::class);

$select = Bootstrap::getObjectManager()->create(
    Attribute::class,
    [
        'data' => [
            'frontend_input' => 'file',
            'frontend_label' => ['test_file_attribute'],
            'sort_order' => 1,
            'backend_type' => 'varchar',
            'is_user_defined' => 1,
            'is_system' => 0,
            'is_used_in_grid' => 1,
            'is_required' => '0',
            'is_visible' => 1,
            'used_in_forms' => [
                'customer_account_create',
                'customer_account_edit',
                'adminhtml_checkout'
            ],
            'attribute_set_id' => $entityType->getDefaultAttributeSetId(),
            'attribute_group_id' => $attributeSet->getDefaultGroupId($entityType->getDefaultAttributeSetId()),
            'entity_type_id' => $entityType->getId(),
            'default_value' => '',
        ],
    ]
);
$select->setAttributeCode('test_file_attribute');
$select->save();
