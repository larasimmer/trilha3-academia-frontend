<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;
use Magento\GiftRegistry\Model\Entity;

Resolver::getInstance()->requireDataFixture('Magento/GiftRegistry/_files/gift_registry_entity_simple.php');

/** @var \Magento\Framework\ObjectManagerInterface  $objectManager */
$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
/** @var Entity $entity */
$entity =$objectManager->get(Entity::class);
$entity->loadByUrlKey('gift_regidtry_simple_url');
/** @var \Magento\GiftRegistry\Model\Person $person */
$person = $objectManager->create(
    \Magento\GiftRegistry\Model\Person::class,
    [
        'data' => [
            'entity_id' => $entity->getId(),
            'firstname' => 'First',
            'lastname' => 'Last',
            'email' => 'fist.last@magento.com',
            'role' => 'Role',
            'custom' => [
                'key' => 'value',
            ],
        ],
    ]
);
$person->setHasDataChanges(true);
$person->save();
