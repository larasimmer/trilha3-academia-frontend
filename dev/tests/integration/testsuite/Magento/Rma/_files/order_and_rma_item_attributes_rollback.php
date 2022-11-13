<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Rma\Model\Item;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;
use Magento\TestFramework\Helper\Bootstrap;

Resolver::getInstance()->requireDataFixture('Magento/Sales/_files/customer_order_with_two_items_rollback.php');
$objectManager = Bootstrap::getObjectManager();
$attributeRepository = $objectManager->get(AttributeRepositoryInterface::class);

$enteredAttribute = $attributeRepository->get(Item::ENTITY, 'entered_item_attribute');
$attributeRepository->delete($enteredAttribute);

$selectedAttribute = $attributeRepository->get(Item::ENTITY, 'selected_rma_item_attribute');
$attributeRepository->delete($selectedAttribute);
