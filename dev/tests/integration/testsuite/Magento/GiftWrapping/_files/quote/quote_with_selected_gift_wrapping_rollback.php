<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\GiftWrapping\Model\Wrapping as GiftWrapping;
use Magento\GiftWrapping\Model\ResourceModel\Wrapping as GiftWrappingResource;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResource;
use Magento\Quote\Model\Quote;

/** @var ObjectManagerInterface $objectManager */
$objectManager = Bootstrap::getObjectManager();

$images = [
    'image1.png',
    'image2.png',
    'image3.png',
    'image4.png',
];

/**
 * @var GiftWrappingResource $giftWrappingResource
 * @var GiftWrapping $giftWrappingModel
 */
$giftWrappingResource = $objectManager->create(GiftWrappingResource::class);
$giftWrappingModel = $objectManager->create(GiftWrapping::class);

foreach ($images as $value) {
    $giftWrappingResource->load($giftWrappingModel, $value, 'image')->delete($giftWrappingModel);
}

/**
 * @var QuoteResource $quote
 * @var Quote $quoteModel
 */
$quote = $objectManager->create(QuoteResource::class);
$quoteModel = $objectManager->create(Quote::class);
$quote->load($quoteModel, 'test_quote_with_selected_gift_wrapping', 'reserved_order_id')
    ->delete($quoteModel);
