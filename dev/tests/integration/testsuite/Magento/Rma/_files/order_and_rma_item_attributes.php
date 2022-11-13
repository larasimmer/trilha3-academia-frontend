<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

use Magento\CustomAttributeManagement\Helper\Data;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\Set;
use Magento\Rma\Model\Item;
use Magento\Rma\Model\Item\AttributeFactory;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/Sales/_files/customer_order_with_two_items.php');

$objectManager = Bootstrap::getObjectManager();
$attributeRepository = $objectManager->get(AttributeRepositoryInterface::class);
$attributeFactory = $objectManager->get(AttributeFactory::class);
$helper = $objectManager->get(Data::class);
$orderRepository = $objectManager->get(OrderRepositoryInterface::class);

//Create entered attribute

$attributeData['frontend_input'] = 'text';
$attributeData['attribute_code'] = 'entered_item_attribute';
$attributeData['is_visible'] = '1';
$attributeData['sort_order'] = '9';
$attributeData['frontend_label'] = [
    'entered_item_attribute',
    ''
];
$attributeData['default_value_text'] = 'magento';
$attributeData['backend_model'] = $helper->getAttributeBackendModelByInputType($attributeData['frontend_input']);
$attributeData['source_model'] = $helper->getAttributeSourceModelByInputType($attributeData['frontend_input']);
$attributeData['backend_type'] = $helper->getAttributeBackendTypeByInputType($attributeData['frontend_input']);
$attributeData['entity_type_id'] = $objectManager->get(Config::class)->getEntityType(Item::ENTITY)->getId();
$attributeData['is_user_defined'] = 1;
$attributeData['is_system'] = 0;
$attributeData['attribute_set_id'] = $attributeData['entity_type_id'];
$attributeData['attribute_group_id'] = $objectManager->create(Set::class)
    ->getDefaultGroupId($attributeData['attribute_set_id']);
$attributeData['used_in_forms'] = ['default'];

$attribute = $attributeFactory->create();
$attribute->addData($attributeData);
$attribute->setCanManageOptionLabels(true);
$attributeRepository->save($attribute);

//Create selected attribute

$attributeData2['frontend_input'] = 'select';
$attributeData2['attribute_code'] = 'selected_rma_item_attribute';
$attributeData2['is_visible'] = '1';
$attributeData2['sort_order'] = '8';
$attributeData2['frontend_label'] = [
    'selected_rma_item_attribute',
    ''
];

$attributeData2['backend_model'] = $helper->getAttributeBackendModelByInputType($attributeData2['frontend_input']);
$attributeData2['source_model'] = $helper->getAttributeSourceModelByInputType($attributeData2['frontend_input']);
$attributeData2['backend_type'] = $helper->getAttributeBackendTypeByInputType($attributeData2['frontend_input']);
$attributeData2['entity_type_id'] = $objectManager->get(Config::class)->getEntityType(Item::ENTITY)->getId();
$attributeData2['is_user_defined'] = 1;
$attributeData2['is_system'] = 0;
$attributeData2['attribute_set_id'] = $attributeData2['entity_type_id'];
$attributeData2['attribute_group_id'] = $objectManager->create(Set::class)
    ->getDefaultGroupId($attributeData2['attribute_set_id']);
$attributeData2['used_in_forms'] = ['default'];

$optionData = [
    'option' => [
        'order' => [
            'option_0' => '1',
            'option_1' => '2'
        ],
        'value' => [
            'option_0' => ['first', 'first'],
            'option_1' => ['second', 'second']
        ],
        'delete' => [
            'option_0' => '',
            'option_1' => ''
        ]
    ],
    'default' => ['option_0']
];

$attributeData2 = array_replace_recursive(
    $attributeData2,
    $optionData
);

$attribute2 = $attributeFactory->create();
$attribute2->addData($attributeData2);
$attribute2->setCanManageOptionLabels(true);
$attributeRepository->save($attribute2);

$orderFactory = $objectManager->get(OrderInterfaceFactory::class);
$order = $orderFactory->create()->loadByIncrementId('100000555');
$items = $order->getItems();
$updatedItems = [];
foreach ($items as $item) {
    $item->setQtyInvoiced(1);
    $item->setQtyShipped(1);
    $updatedItems[] = $item;
}

$order->setState(Order::STATE_PROCESSING)
    ->setStatus(Order::STATE_PROCESSING)
    ->setItems($updatedItems);

$orderRepository->save($order);
