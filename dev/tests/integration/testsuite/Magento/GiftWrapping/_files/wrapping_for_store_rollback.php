<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\GiftWrapping\Model\Wrapping;
use Magento\TestFramework\Helper\Bootstrap;

$wrapping = Bootstrap::getObjectManager()
    ->create(Wrapping::class);
$wrapping->load('wrapping_for_store.png', 'image')
    ->delete();
