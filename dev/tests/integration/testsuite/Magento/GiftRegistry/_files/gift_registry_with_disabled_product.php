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
use Magento\GiftRegistry\Model\ItemFactory;
use Magento\GiftRegistry\Model\ResourceModel\Item as ItemResource;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/GiftRegistry/_files/gift_registry_entity_birthday_type.php');
Resolver::getInstance()->requireDataFixture('Magento/Catalog/_files/simple_product_disabled.php');
$objectManager = ObjectManager::getInstance();

/** @var Entity $giftRegistry */
$giftRegistry = $objectManager->get(EntityFactory::class)->create();
$giftRegistry->loadByUrlKey('gift_registry_birthday_type_url');

/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);
$product = $productRepository->get('product_disabled');

/** @var Item $item */
$item = $objectManager->get(ItemFactory::class)->create();
$item->setEntityId($giftRegistry->getId())
    ->setProductId($product->getId())
    ->setQty(1);

/** @var ItemResource $itemResource */
$itemResource = $objectManager->get(ItemResource::class);
$itemResource->save($item);
