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
use Magento\Eav\Model\Entity\Attribute\Source\Table;
use Magento\Eav\Model\Entity\Type;
use Magento\Framework\Event\ManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
/** @var Type $entityType */
$entityType = $objectManager->get(Config::class)->getEntityType('customer');
/** @var Set $attributeSet */
$attributeSet = $objectManager->get(Set::class);
/** @var AttributeFactory $attributeFactory */
$attributeFactory = $objectManager->get(AttributeFactory::class);
/** @var AttributeResource $attributeResource */
$attributeResource = $objectManager->get(AttributeResource::class);
/** @var ManagerInterface $eventManager */
$eventManager = $objectManager->get(ManagerInterface::class);

$data = [
    AttributeInterface::ATTRIBUTE_CODE => 'customer_attribute_type_select',
    AttributeInterface::FRONTEND_INPUT => 'select',
    AttributeInterface::FRONTEND_LABEL => ['select_attribute'],
    'sort_order' => '0',
    AttributeInterface::BACKEND_TYPE => 'int',
    AttributeInterface::IS_USER_DEFINED => 1,
    'is_system' => 0,
    AttributeInterface::IS_REQUIRED => '0',
    'is_visible' => '1',
    'option' => [
        'value' => ['option_0' => ['Option 1'], 'option_1' => ['Option 2']],
        'order' => ['option_0' => 1, 'option_1' => 2, 'option_2' => 3],
    ],
    'attribute_set_id' => $entityType->getDefaultAttributeSetId(),
    'attribute_group_id' => $attributeSet->getDefaultGroupId($entityType->getDefaultAttributeSetId()),
    AttributeInterface::ENTITY_TYPE_ID => $entityType->getId(),
    'used_in_forms' => ['customer_account_create'],
    AttributeInterface::SOURCE_MODEL => Table::class,
];
$attribute = $attributeFactory->create();
$attribute->setData($data);
$attributeResource->save($attribute);

$eventManager->dispatch(
    'magento_customercustomattributes_attribute_save',
    ['attribute' => $attribute]
);
