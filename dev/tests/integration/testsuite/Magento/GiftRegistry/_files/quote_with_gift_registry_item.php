<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Cart;
use Magento\Framework\DataObjectFactory;
use Magento\GiftRegistry\Model\Entity;
use Magento\GiftRegistry\Model\EntityFactory;
use Magento\GiftRegistry\Model\Item;
use Magento\GiftRegistry\Model\ItemFactory;
use Magento\GiftRegistry\Model\ResourceModel\Item as ItemResource;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/GiftRegistry/_files/gift_registry_entity_birthday_type.php');
Resolver::getInstance()->requireDataFixture('Magento/Catalog/_files/products.php');

Bootstrap::getInstance()->loadArea('frontend');

$objectManager = Bootstrap::getObjectManager();
/** @var DataObjectFactory $dataObjectFactory */
$dataObjectFactory = $objectManager->get(DataObjectFactory::class);
/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);
$product = $productRepository->get('simple');

/** @var Entity $giftRegistry */
$giftRegistry = $objectManager->get(EntityFactory::class)->create();
$giftRegistry->loadByUrlKey('gift_registry_birthday_type_url');
/** @var Item $giftRegistryItem */
$giftRegistryItem = $objectManager->get(ItemFactory::class)->create();
$giftRegistryItem->setEntityId($giftRegistry->getId())
    ->setProductId($product->getId())
    ->setQty(1);
/** @var ItemResource $giftRegistryItemResource */
$giftRegistryItemResource = $objectManager->get(ItemResource::class);
$giftRegistryItemResource->save($giftRegistryItem);

$product->setGiftregistryItemId($giftRegistryItem->getId());
$product->addCustomOption('giftregistry_id', $giftRegistryItem->getEntityId());

$requestInfo = $dataObjectFactory->create(
    [
        'data' => [
            'product' => $product->getId(),
            'qty' => 1,
            'original_qty' => 1,
        ],
    ]
);

/** @var Cart $cart */
$cart = $objectManager->get(Cart::class);
$cart->addProduct($product, $requestInfo);
$cart->getQuote()->setReservedOrderId('test_cart_gift_registry_item');
$cart->save();

$objectManager->removeSharedInstance(\Magento\Checkout\Model\Session::class);
