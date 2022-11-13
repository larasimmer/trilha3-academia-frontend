<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Customer\Model\AttributeFactory;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\ResourceModel\Attribute as AttributeResource;
use Magento\Framework\Event\ManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
/** @var AttributeResource $attributeResource */
$attributeResource = $objectManager->get(AttributeResource::class);
/** @var ManagerInterface $eventManager */
$eventManager = $objectManager->get(ManagerInterface::class);
/** @var AttributeFactory $attributeFactory */
$attributeFactory = $objectManager->get(AttributeFactory::class);

$attribute = $attributeFactory->create()->loadByCode(Customer::ENTITY, 'customer_attribute_type_select');
if ($attribute->getId()) {
    $attributeResource->delete($attribute);
    $eventManager->dispatch(
        'magento_customercustomattributes_attribute_delete',
        ['attribute' => $attribute]
    );
}
