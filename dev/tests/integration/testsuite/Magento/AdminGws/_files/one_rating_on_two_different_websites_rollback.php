<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Review\Model\Rating;
use Magento\Review\Model\ResourceModel\Rating as RatingResourceModel;
use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

$objectManager = Bootstrap::getObjectManager();

/** @var RatingResourceModel $ratingResourceModel */
$ratingResourceModel = $objectManager->create(RatingResourceModel::class);

$rating = $objectManager->get(Rating::class);

/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

$rating->load(1);
$rating->setStores([]);
$ratingResourceModel->save($rating);

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);

Resolver::getInstance()->requireDataFixture('Magento/AdminGws/_files/two_users_on_different_websites_rollback.php');
