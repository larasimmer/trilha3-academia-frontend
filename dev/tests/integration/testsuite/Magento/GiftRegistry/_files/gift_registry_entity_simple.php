<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Customer\Model\CustomerRegistry;
use Magento\Store\Model\StoreManager;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;
use Magento\TestFramework\Helper\Bootstrap;

Resolver::getInstance()->requireDataFixture('Magento/Customer/_files/customer_with_website.php');

/** @var \Magento\Framework\ObjectManagerInterface  $objectManager */
$objectManager = Bootstrap::getObjectManager();
/** @var StoreManager $storeManager */
$storeManager = $objectManager->get(StoreManager::class);
/** @var CustomerRegistry $customerRegistry */
$customerRegistry = $objectManager->create(CustomerRegistry::class);
$customer = $customerRegistry->retrieveByEmail(
    'john.doe@magento.com',
    $storeManager->getDefaultStoreView()->getWebsiteId()
);
/** @var \Magento\GiftRegistry\Model\Entity $entity */
$entity = $objectManager->create(
    \Magento\GiftRegistry\Model\Entity::class,
    [
        'data' => [
            'type_id' => 1, //birtday from magento_giftregistry_type table
            'customer_id' => $customer->getId(),
            'website_id' => $customer->getWebsiteId(),
            'is_public' => 0,
            'url_key' => 'gift_regidtry_simple_url',
            'title' => 'Gift Registry',
            'is_active' => true,
        ],
    ]
);
$address = $objectManager->create(
    \Magento\Customer\Model\Address::class,
    [
        'data' => [
            'street' => 'some street',
        ],
    ]
);

$entity->importAddress($address);
$entity->save();
