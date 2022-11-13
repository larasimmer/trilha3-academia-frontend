<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\GiftRegistry\Model\EntityFactory;
use Magento\GiftRegistry\Model\ResourceModel\Entity;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/Customer/_files/customer_with_website_rollback.php');

$objectManager = Bootstrap::getObjectManager();
/** @var EntityFactory $giftRegistryFactory */
$giftRegistryFactory = $objectManager->get(EntityFactory::class);
/** @var Entity $giftRegistryResource */
$giftRegistryResource = $objectManager->get(Entity::class);

$giftRegistry = $giftRegistryFactory->create()->loadByUrlKey('gift_regidtry_simple_url');
if ($giftRegistry->getId()) {
    $giftRegistryResource->delete($giftRegistry);
}
