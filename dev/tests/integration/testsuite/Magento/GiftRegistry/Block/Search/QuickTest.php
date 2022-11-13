<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Block\Search;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Test for gift registry on quick search block.
 *
 * @see \Magento\GiftRegistry\Block\Search\Quick
 * @magentoAppArea frontend
 */
class QuickTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var Quick */
    private $block;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->block = $this->objectManager->get(LayoutInterface::class)->createBlock(Quick::class);
    }

    /**
     * @magentoConfigFixture current_store magento_giftregistry/general/enabled 1
     *
     * @return void
     */
    public function testGiftRegistryEnabled(): void
    {
        $this->assertTrue($this->block->getEnabled());
    }

    /**
     * @magentoConfigFixture current_store magento_giftregistry/general/enabled 0
     *
     * @return void
     */
    public function testGiftRegistryDisabled(): void
    {
        $this->assertFalse($this->block->getEnabled());
    }
}
