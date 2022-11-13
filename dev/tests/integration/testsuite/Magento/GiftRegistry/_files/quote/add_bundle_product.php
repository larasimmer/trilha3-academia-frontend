<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

use Magento\Bundle\Model\Option;
use Magento\Bundle\Model\Product\Type;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\DataObjectFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResource;

Resolver::getInstance()->requireDataFixture('Magento/Bundle/_files/fixed_bundle_product_without_discounts.php');

/** @var DataObjectFactory $dataObjectFactory */
$dataObjectFactory = Bootstrap::getObjectManager()->get(DataObjectFactory::class);
/** @var ProductRepositoryInterface $productRepository */
$productRepository = Bootstrap::getObjectManager()->get(ProductRepositoryInterface::class);
/** @var QuoteFactory $quoteFactory */
$quoteFactory = Bootstrap::getObjectManager()->get(QuoteFactory::class);
/** @var QuoteResource $quoteResource */
$quoteResource = Bootstrap::getObjectManager()->get(QuoteResource::class);
/** @var CartRepositoryInterface $cartRepository */
$cartRepository = Bootstrap::getObjectManager()->get(CartRepositoryInterface::class);

$product = $productRepository->get('fixed_bundle_product_without_discounts');
/** @var Type $typeInstance */
$typeInstance = $product->getTypeInstance();
$typeInstance->setStoreFilter($product->getStoreId(), $product);
$optionCollection = $typeInstance->getOptionsCollection($product);

$bundleOptions = [];
$bundleOptionsQty = [];
$optionsData = [];
foreach ($optionCollection as $option) {
    /** @var Option $option */
    $selectionsCollection = $typeInstance->getSelectionsCollection([$option->getId()], $product);
    if ($option->isMultiSelection()) {
        $optionsData[$option->getId()] = array_column($selectionsCollection->toArray(), 'product_id');
        $bundleOptions[$option->getId()] = array_column($selectionsCollection->toArray(), 'selection_id');
    } else {
        $bundleOptions[$option->getId()] = $selectionsCollection->getFirstItem()->getSelectionId();
        $optionsData[$option->getId()] = $selectionsCollection->getFirstItem()->getProductId();
    }
    $bundleOptionsQty[$option->getId()] = 1;
}

$requestInfo = $dataObjectFactory->create(
    [
        'data' => [
            'product' => $product->getId(),
            'bundle_option' => $bundleOptions,
            'bundle_option_qty' => $bundleOptionsQty,
            'qty' => 1,
        ],
    ]
);

$quote = $quoteFactory->create();
$quoteResource->load($quote, 'test_quote', 'reserved_order_id');
$quote->addProduct($product, $requestInfo);
$cartRepository->save($quote);
