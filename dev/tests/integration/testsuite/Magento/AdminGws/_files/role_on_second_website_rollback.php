<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/User/_files/user_with_custom_role_rollback.php');
Resolver::getInstance()->requireDataFixture('Magento/Store/_files/second_website_with_store_group_and_store_rollback.php');
