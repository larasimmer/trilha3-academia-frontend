<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\BannerCustomerSegment\Block\Adminhtml\Banner\Edit\Tab;

use Magento\Banner\Block\Adminhtml\Banner\Edit\Tab\Properties;
use Magento\Banner\Model\Banner;
use Magento\Banner\Model\BannerFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\LayoutInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Helper\Xpath;
use PHPUnit\Framework\TestCase;

/**
 * Test for customer segment fields on banner properties block.
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class PropertiesTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var Registry */
    private $registry;

    /** @var Properties */
    private $block;

    /** @var BannerFactory */
    private $bannerFactory;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->registry = $this->objectManager->get(Registry::class);
        $this->block = $this->objectManager->get(LayoutInterface::class)->createBlock(Properties::class);
        $this->bannerFactory = $this->objectManager->get(BannerFactory::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        $this->registry->unregister('current_banner');

        parent::tearDown();
    }

    /**
     * @magentoConfigFixture current_store customer/magento_customersegment/is_enabled 1
     * @magentoDataFixture Magento/CustomerSegment/_files/segment.php
     *
     * @return void
     */
    public function testCustomerSegmentsFields(): void
    {
        $banner = $this->bannerFactory->create();
        $this->registerBanner($banner);
        $html = $this->block->toHtml();
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath("//select[@id='banner_properties_use_customer_segment']", $html),
            'Customer Segment field wasn\'t found.'
        );
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath("//select[@id='banner_properties_customer_segment_ids']", $html),
            'Customer Segment ids field wasn\'t found.'
        );
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(
                "//select[@id='banner_properties_customer_segment_ids']"
                . "/option[contains(text(), 'Customer Segment 1')]",
                $html
            ),
            'Option in field customer segment ids wasn\'t found.'
        );
    }

    /**
     * @magentoConfigFixture current_store customer/magento_customersegment/is_enabled 0
     *
     * @return void
     */
    public function testCustomerSegmentsFieldsDisabled(): void
    {
        $banner = $this->bannerFactory->create();
        $this->registerBanner($banner);
        $html = $this->block->toHtml();
        $this->assertEquals(
            0,
            Xpath::getElementsCountForXpath("//select[@id='rule_use_customer_segment']", $html),
            'Customer Segment field was found with disabled config.'
        );
        $this->assertEquals(
            0,
            Xpath::getElementsCountForXpath("//select[@id='banner_properties_customer_segment_ids']", $html),
            'Customer Segment ids field was found with disabled config.'
        );
    }

    /**
     * Register banner in registry.
     *
     * @param Banner $banner
     * @return void
     */
    private function registerBanner(Banner $banner): void
    {
        $this->registry->unregister('current_banner');
        $this->registry->register('current_banner', $banner);
    }
}
