<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Cms\Api\Data\PageInterfaceFactory;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Model\Page;
use Magento\Cms\Model\PageFactory;
use Magento\Cms\Model\ResourceModel\Page as PageResource;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
/** @var PageInterfaceFactory $pageFactory */
$pageFactory = $objectManager->get(PageFactory::class);
/** @var PageResource $pageResource */
$pageResource = $objectManager->get(PageResource::class);
/** @var PageRepositoryInterface $pageRepository */
$pageRepository = $objectManager->get(PageRepositoryInterface::class);

$firstPage = $pageFactory->create();
$pageResource->load($firstPage, 'page-1', Page::IDENTIFIER);
if ($firstPage->getId() !== null) {
    $pageRepository->delete($firstPage);
}

$secondPage = $pageFactory->create();
$pageResource->load($secondPage, 'page-2', Page::IDENTIFIER);
if ($firstPage->getId() !== null) {
    $pageRepository->delete($secondPage);
}
