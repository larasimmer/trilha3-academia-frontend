<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Banner\Model;

use Magento\Banner\Model\ResourceModel\Banner as BannerResource;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * Banner model test class
 */
class BannerTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var BannerResource
     */
    private $resourceModel;

    /**
     * @var BannerFactory
     */
    private $bannerFactory;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->resourceModel = $this->objectManager->get(BannerResource::class);
        $this->bannerFactory = $this->objectManager->get(BannerFactory::class);
    }

    /**
     * Test if dynamic block can be saved with empty content
     *
     * @magentoDataFixture Magento/Banner/_files/banner.php
     */
    public function testSaveWithEmptyContent(): void
    {
        $banner = $this->bannerFactory->create();
        $this->resourceModel->load($banner, 'Test Dynamic Block', 'name');
        $banner->setStoreContents([0 => null]);
        $this->resourceModel->save($banner);
        $this->assertEquals([0 => ''], $banner->getStoreContents());
    }
}
