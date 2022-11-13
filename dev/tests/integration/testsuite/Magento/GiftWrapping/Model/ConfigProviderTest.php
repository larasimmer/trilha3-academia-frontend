<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftWrapping\Model;

use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Test for GiftWrapping config provider
 */
class ConfigProviderTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var ConfigProvider
     */
    private $model;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->model = $this->objectManager->get(ConfigProvider::class);
    }

    /**
     * Verifies wrapping design value for a particular store
     *
     * @magentoDataFixture Magento/GiftWrapping/_files/wrapping_for_store.php
     * @return void
     */
    public function testGiftWrappingDesignValueForStore(): void
    {
        $expectedDesign = 'Test Wrapping for store 1';
        /** @var StoreManagerInterface $storeManager */
        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $storeManager->setCurrentStore(1);
        $designCollection = $this->model->getDesignCollection();
        $wrapping = $designCollection->getFirstItem();
        $actualDesign = $wrapping->getDesign();
        $this->assertEquals($expectedDesign, $actualDesign);
    }
}
