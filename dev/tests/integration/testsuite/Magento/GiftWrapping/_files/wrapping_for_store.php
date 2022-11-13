<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\GiftWrapping\Model\Wrapping;
use Magento\Store\Model\Store;
use Magento\TestFramework\Helper\Bootstrap;

$wrapping = Bootstrap::getObjectManager()
    ->create(Wrapping::class);
$wrapping->setDesign('Test Wrapping')
    ->setStatus(1)
    ->setBasePrice(5.00)
    ->setImage('wrapping_for_store.png')
    ->setStoreId(Store::DEFAULT_STORE_ID)
    ->save();
$wrapping = $wrapping->load('wrapping_for_store.png', 'image');
$wrapping->setDesign('Test Wrapping for store 1')
    ->setStatus(1)
    ->setBasePrice(5.00)
    ->setImage('wrapping_for_store.png')
    ->setStoreId(1)
    ->save();
