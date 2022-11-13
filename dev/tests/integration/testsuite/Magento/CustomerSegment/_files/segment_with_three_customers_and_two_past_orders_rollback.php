<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/Customer/_files/three_customers_rollback.php');
Resolver::getInstance()->requireDataFixture('Magento/Sales/_files/order_list_rollback.php');
Resolver::getInstance()->requireDataFixture('Magento/CustomerSegment/_files/segment_rollback.php');
