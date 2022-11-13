<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/GiftRegistry/_files/gift_registry_entity_birthday_type_rollback.php');
Resolver::getInstance()->requireDataFixture('Magento/Catalog/_files/products_rollback.php');
Resolver::getInstance()->requireDataFixture('Magento/Checkout/_files/rollback_quote.php');
