<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Model\Config;
use Magento\Rma\Model\Item;
use Magento\Rma\Model\Item\AttributeFactory;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
$attributeRepository = $objectManager->get(AttributeRepositoryInterface::class);
$attribute = $objectManager->get(AttributeFactory::class)->create();
$entityTypeId = $objectManager->get(Config::class)->getEntityType(Item::ENTITY)->getId();

$attribute->setAttributeCode('rma_item_attribute')
    ->setEntityTypeId($entityTypeId)
    ->setFrontendInput('text')
    ->setFrontendLabel('Rma Item Attribute')
    ->setIsUserDefined(1);
$attributeRepository->save($attribute);
