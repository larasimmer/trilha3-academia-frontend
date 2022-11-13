<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Customer\Model\AttributeFactory;
use Magento\Customer\Model\ResourceModel\Attribute as AttributeResource;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\Set;
use Magento\Eav\Model\Entity\Type;
use Magento\TestFramework\Helper\Bootstrap;

/** @var $entityType Type */
$objectManager = Bootstrap::getObjectManager();
$entityType = $objectManager->get(Config::class)->getEntityType('customer_address');

/** @var $attributeSet Set */
$attributeSet = $objectManager ->create(Set::class);

/** @var AttributeFactory $attributeFactory */
$attributeFactory = $objectManager->get(AttributeFactory::class);
/** @var AttributeResource $attributeResource */
$attributeResource = $objectManager->get(AttributeResource::class);

$multiSelect = $attributeFactory->create(
    [
        'data' => [
            AttributeInterface::FRONTEND_INPUT => 'multiselect',
            AttributeInterface::FRONTEND_LABEL => ['multi_select_attribute'],
            'sort_order' => '0',
            AttributeInterface::BACKEND_TYPE => 'varchar',
            AttributeInterface::IS_USER_DEFINED => 1,
            'is_system' => 0,
            AttributeInterface::IS_REQUIRED => '0',
            'is_visible' => '0',
            'option' => [
                'value' => ['option_0' => ['Option 1'], 'option_1' => ['Option 2'], 'option_2' => ['Option 3']],
                'order' => ['option_0' => 1, 'option_1' => 2, 'option_2' => 3],
            ],
            'attribute_set_id' => $entityType->getDefaultAttributeSetId(),
            'attribute_group_id' => $attributeSet->getDefaultGroupId($entityType->getDefaultAttributeSetId()),
            AttributeInterface::ENTITY_TYPE_ID => $entityType->getId(),
            'default_value' => '',
            'source_model' => Magento\Eav\Model\Entity\Attribute\Source\Table::class,
        ],
    ]
);
$multiSelect->setAttributeCode('multi_select_code');
$attributeResource->save($multiSelect);

$select = $attributeFactory->create(
    [
        'data' => [
            AttributeInterface::FRONTEND_INPUT => 'select',
            AttributeInterface::FRONTEND_LABEL => ['test_select_code'],
            'sort_order' => '0',
            AttributeInterface::BACKEND_TYPE => 'int',
            AttributeInterface::IS_USER_DEFINED => 1,
            'is_system' => 0,
            AttributeInterface::IS_REQUIRED => '0',
            'is_visible' => '0',
            'option' => [
                'value' => ['option_0' => ['First'], 'option_1' => ['Second']],
                'order' => ['option_0' => 1, 'option_1' => 2],
            ],
            'attribute_set_id' => $entityType->getDefaultAttributeSetId(),
            'attribute_group_id' => $attributeSet->getDefaultGroupId($entityType->getDefaultAttributeSetId()),
            AttributeInterface::ENTITY_TYPE_ID => $entityType->getId(),
            'default_value' => '',
            'source_model' => Magento\Eav\Model\Entity\Attribute\Source\Table::class,
        ],
    ]
);
$select->setAttributeCode('select_code');
$attributeResource->save($select);

$text = $attributeFactory->create([
    'data' => [
        AttributeInterface::FRONTEND_INPUT => 'text',
        AttributeInterface::FRONTEND_LABEL => ['text_code'],
        'sort_order' => '0',
        AttributeInterface::BACKEND_TYPE => 'varchar',
        AttributeInterface::IS_USER_DEFINED => 1,
        'is_system' => 0,
        AttributeInterface::IS_REQUIRED => '0',
        'is_visible' => '0',
        'attribute_set_id' => $entityType->getDefaultAttributeSetId(),
        'attribute_group_id' => $attributeSet->getDefaultGroupId($entityType->getDefaultAttributeSetId()),
        AttributeInterface::ENTITY_TYPE_ID => $entityType->getId(),
        'default_value' => '',
    ],
]);
$text->setAttributeCode('test_custom_address_text_code');
$attributeResource->save($text);
