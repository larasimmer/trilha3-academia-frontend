<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Review\Model\Rating;
use Magento\Review\Model\ResourceModel\Rating as RatingResourceModel;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/AdminGws/_files/two_users_on_different_websites.php');

$objectManager = Bootstrap::getObjectManager();

/** @var StoreRepositoryInterface $storeRepository */
$storeRepository = $objectManager->get(StoreRepositoryInterface::class);
$storeId = $storeRepository->get('test_store_view')->getId();

/** @var RatingResourceModel $ratingResourceModel */
$ratingResourceModel = $objectManager->create(RatingResourceModel::class);

$rating = $objectManager->get(Rating::class);
$rating->load(1);
$rating->setStores([$storeId]);
$ratingResourceModel->save($rating);
