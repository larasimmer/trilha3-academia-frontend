<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

$registry = $objectManager->get(\Magento\Framework\Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

for ($i = 1; $i <= 2; $i++) {
    $wishlist = $objectManager->create(\Magento\Wishlist\Model\Wishlist::class);
    $wishlist->loadByCode('wishlist_fixture_' . $i);
    if ($wishlist->getId()) {
        $wishlist->delete();
    }
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);

Resolver::getInstance()->requireDataFixture('Magento/MultipleWishlist/_files/products_rollback.php');
Resolver::getInstance()->requireDataFixture('Magento/Customer/_files/two_customers_rollback.php');
