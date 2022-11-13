<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
/** @var $product \Magento\Catalog\Model\Product */
$product = $objectManager->create(\Magento\Catalog\Model\Product::class);

$amountData = [
    'value' => 10,
    'website_id' => 0,
    'attribute_id' => 132,
];

$extensionAttributes = $objectManager->create(\Magento\Catalog\Api\Data\ProductExtension::class);
$giftCardAmountFactory = $objectManager->create(\Magento\GiftCard\Api\Data\GiftcardAmountInterfaceFactory::class);
$amount = $giftCardAmountFactory->create(['data' => $amountData]);
$extensionAttributes->setGiftcardAmounts([$amount]);
$product->setTypeId(\Magento\GiftCard\Model\Catalog\Product\Type\Giftcard::TYPE_GIFTCARD)
    ->setAttributeSetId($product->getDefaultAttributeSetId())
    ->setWebsiteIds([1])
    ->setName('Gift Card with fixed amount 10')
    ->setSku('gift-card-with-fixed-amount-10')
    ->setDescription('Gift Card With Fixed Amount Description')
    ->setMetaTitle('Gift Card With Fixed Amount Meta Title')
    ->setMetaKeyword('Gift CardWith Fixed Amount  Meta Keyword')
    ->setMetaDescription('Gift Card With Fixed Amount Meta Description')
    ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
    ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
    ->setCategoryIds([2])
    ->setStockData(['use_config_manage_stock' => 0])
    ->setCanSaveCustomOptions(true)
    ->setHasOptions(true)
    ->setGiftcardType(\Magento\GiftCard\Model\Giftcard::TYPE_PHYSICAL)
    ->setAllowOpenAmount(0)
    ->setExtensionAttributes($extensionAttributes)
    ->save();
