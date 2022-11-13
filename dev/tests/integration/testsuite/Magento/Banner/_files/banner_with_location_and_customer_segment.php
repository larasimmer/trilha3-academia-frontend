<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Banner\Model\Banner;
use Magento\BannerCustomerSegment\Model\ResourceModel\BannerSegmentLink;
use Magento\CustomerSegment\Model\SegmentFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

/**
 * Creates banner with enabled status, locations and customer segments
 */

Resolver::getInstance()->requireDataFixture('Magento/CustomerSegment/_files/segment_customers.php');
Resolver::getInstance()->requireDataFixture('Magento/Customer/_files/customer_with_addresses.php');

$objectManager = Bootstrap::getObjectManager();

/** @var $banner Banner */
$banner = $objectManager->create(Banner::class);

/** @var $bannerSegmentLink BannerSegmentLink */
$bannerSegmentLink = $objectManager->create(BannerSegmentLink::class);

/** @var $segmentFactory SegmentFactory */
$segmentFactory = $objectManager->get(SegmentFactory::class);

$banner->setIsEnabled(
    Banner::STATUS_ENABLED
)->setName(
    'Test Dynamic Block with location and segment'
)->setTypes(
    'footer header'
)->setStoreContents(
    [0 => '<p>Dynamic Block Content with location and segment</p>']
)->save();


$segment = $segmentFactory->create();
$segment->load('Customer Segment 1', 'name');
$bannerSegmentLink->saveBannerSegments($banner->getId(), [$segment->getId()]);
