<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Catalog\Model\Category;
use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;

$registry = Bootstrap::getObjectManager()->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var $category Category */
$category = Bootstrap::getObjectManager()->create(Category::class);
$category->load(3);
if ($category->getId()) {
    $category->delete();
}
$category->load(4);
if ($category->getId()) {
    $category->delete();
}

Bootstrap::getInstance()->reinitialize();

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
