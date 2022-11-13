<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Customer\Model\AttributeFactory;
use Magento\Customer\Model\ResourceModel\Attribute as AttributeResource;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\Set;
use Magento\Eav\Model\Entity\Attribute\SetFactory;
use Magento\Eav\Model\Entity\Attribute\Source\Table;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
$entityType = $objectManager->get(Config::class)->getEntityType('customer_address');
/** @var Set $attributeSet */
$attributeSet = $objectManager->get(SetFactory::class)->create();
/** @var AttributeFactory $attributeFactory */
$attributeFactory = $objectManager->get(AttributeFactory::class);
/** @var AttributeResource $attributeResource */
$attributeResource = $objectManager->get(AttributeResource::class);
$attribute = $attributeFactory->create([
    'data' => [
        AttributeInterface::FRONTEND_INPUT => 'text',
        AttributeInterface::FRONTEND_LABEL => ['test_text_code'],
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
        AttributeInterface::SOURCE_MODEL => Table::class,
    ]
]);
$attribute->setAttributeCode('test_text_code');
$attributeResource->save($attribute);
