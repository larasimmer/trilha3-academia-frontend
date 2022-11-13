<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Store\Api\WebsiteRepositoryInterface;

/** @var $product \Magento\Catalog\Model\Product */
$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
$product = $objectManager->create(\Magento\Catalog\Model\Product::class);

/** @var WebsiteRepositoryInterface $repository */
$websiteRepository = $objectManager->get(WebsiteRepositoryInterface::class);
$websiteId = $websiteRepository->get('test')->getId();

$giftCardAmountFactory = $objectManager->create(\Magento\GiftCard\Api\Data\GiftcardAmountInterfaceFactory::class);
$amountData = [
    $giftCardAmountFactory->create(
        [
            'data' => [
                'value' => 7,
                'website_id' => 0,
                'attribute_id' => 132
            ]
        ]
    ),
    $giftCardAmountFactory->create(
        [
            'data' => [
                'value' => 17,
                'website_id' => 0,
                'attribute_id' => 132
            ]
        ]
    ),
    $giftCardAmountFactory->create(
        [
            'data' => [
                'value' => 27,
                'website_id' => $websiteId,
                'attribute_id' => 132
            ]
        ]
    ),
    $giftCardAmountFactory->create(
        [
            'data' => [
                'value' => 37,
                'website_id' => $websiteId,
                'attribute_id' => 132
            ]
        ]
    )
];

$extensionAttributes = $objectManager->create(\Magento\Catalog\Api\Data\ProductExtension::class);

$extensionAttributes->setGiftcardAmounts($amountData);

$product->setTypeId(\Magento\GiftCard\Model\Catalog\Product\Type\Giftcard::TYPE_GIFTCARD)
    ->setAttributeSetId(4)
    ->setGiftcardType(0)
    ->setWebsiteIds([1, $websiteId])
    ->setName('Simple Gift Card')
    ->setSku('gift-card-with-amount')
    ->setDescription('Gift Card Description')
    ->setMetaTitle('Gift Card Meta Title')
    ->setMetaKeyword('Gift Card Meta Keyword')
    ->setMetaDescription('Gift Card Meta Description')
    ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
    ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
    ->setCategoryIds([2])
    ->setStockData(['use_config_manage_stock' => 0])
    ->setCanSaveCustomOptions(true)
    ->setHasOptions(true)
    ->setIsReturnable(\Magento\Rma\Model\Product\Source::ATTRIBUTE_ENABLE_RMA_YES)
    ->setIsRedeemable(\Magento\GiftCardAccount\Model\Giftcardaccount::NOT_REDEEMABLE)
    ->setUseConfigLifetime(1)
    ->setUseConfigEmailTemplate(1)
    ->setAllowOpenAmount(1)
    ->setUseConfigAllowMessage(1)
    ->setData('open_amount_min', 100)
    ->setData('open_amount_max', 150)
    ->setUseConfigAllowMessage(0)
    ->setAllowMessage(1)
    ->setGiftMessageAvailable(1)
    ->setExtensionAttributes($extensionAttributes)
    ->save();
