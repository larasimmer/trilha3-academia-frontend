<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Block\Cart;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Test for gift registry link in the shopping cart.
 *
 * @see \Magento\GiftRegistry\Block\Cart\Link
 * @magentoAppArea frontend
 */
class LinkTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var Link */
    private $block;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->block = $this->objectManager->get(LayoutInterface::class)->createBlock(Link::class);
    }

    /**
     * @magentoConfigFixture current_store magento_giftregistry/general/enabled 1
     *
     * @return void
     */
    public function testGiftRegistryLinkEnabled(): void
    {
        $this->assertTrue($this->block->getEnabled());
    }

    /**
     * @magentoConfigFixture current_store magento_giftregistry/general/enabled 0
     *
     * @return void
     */
    public function testGiftRegistryLinkDisabled(): void
    {
        $this->assertFalse($this->block->getEnabled());
    }
}
