<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Customer\Model\Attribute as AttributeModel;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\ResourceModel\Attribute as AttributeResource;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\Set;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
/** @var Config $eavConfig */
$eavConfig = $objectManager->get(Config::class);
$multilineAttribute = $eavConfig->getAttribute(Customer::ENTITY, 'multiline_attribute');

if (!$multilineAttribute->getId()) {
    $entityType = $eavConfig->getEntityType(Customer::ENTITY);
    /** @var Set $attributeSet */
    $attributeSet = $objectManager->create(Set::class);
    /** @var AttributeResource $attributeResource */
    $attributeResource = $objectManager->get(AttributeResource::class);

    /** @var AttributeModel $multilineAttribute */
    $multilineAttribute = $objectManager->create(
        AttributeModel::class,
        [
            'data' => [
                'frontend_input' => 'multiline',
                'frontend_label' => ['multiline_attribute_label'],
                'multiline_count' => 3,
                'sort_order' => '0',
                'backend_type' => 'text',
                'is_user_defined' => 1,
                'is_system' => 0,
                'is_required' => '0',
                'is_visible' => '1',
                'attribute_set_id' => $entityType->getDefaultAttributeSetId(),
                'attribute_group_id' => $attributeSet->getDefaultGroupId($entityType->getDefaultAttributeSetId()),
                'entity_type_id' => $entityType->getId(),
                'default_value' => '',
                'used_in_forms' => ['adminhtml_customer', 'customer_account_create', 'customer_account_edit'],
            ]
        ]
    );
    $multilineAttribute->setAttributeCode('multiline_attribute');
    $attributeResource->save($multilineAttribute);
}

$eavConfig->clear();
