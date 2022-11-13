<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\GiftRegistry\Model\Entity;
use Magento\GiftRegistry\Model\EntityFactory;
use Magento\GiftRegistry\Model\Item;
use Magento\GiftRegistry\Model\Item\OptionFactory;
use Magento\GiftRegistry\Model\ResourceModel\Entity as EntityResource;
use Magento\GiftRegistry\Model\ResourceModel\Item as ItemResource;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/GiftRegistry/_files/gift_registry_entity_birthday_type.php');
Resolver::getInstance()->requireDataFixture('Magento/Catalog/_files/product_simple_out_of_stock.php');
$objectManager = ObjectManager::getInstance();

/** @var Entity $giftRegistry */
$giftRegistry = $objectManager->get(EntityFactory::class)->create();
/** @var EntityResource $giftRegistryResource */
$giftRegistryResource = $objectManager->get(EntityResource::class);
$giftRegistryResource->loadByUrlKey($giftRegistry, 'gift_registry_birthday_type_url');

$productRepository = $objectManager->get(ProductRepositoryInterface::class);
$product= $productRepository->get('simple-out-of-stock');
/** @var Item $item */
$item = $objectManager->create(Item::class);
$item->setEntityId($giftRegistry->getId())->setProductId($product->getId())->setQty(2);
$optionFactory = $objectManager->create(OptionFactory::class);

$option = $optionFactory->create()->setProduct(
    $product
)->setCode(
    'product_qty_' . $product->getId()
)->setValue(
    '1'
)->setItem(
    $item
);
$item->setOptions([$option]);
/** @var ItemResource $itemResource */
$itemResource = $objectManager->get(ItemResource::class);
$itemResource->save($item);
