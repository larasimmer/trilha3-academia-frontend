<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Invitation\Block;

use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Invitation\Model\Config;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Tests for invitation sending form.
 */
class FormTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var MutableScopeConfigInterface
     */
    private $mutableConfig;

    /**
     * @var Form
     */
    private $block;

    /**
     * Remembered old value of store config
     *
     * @var array
     */
    private $rememberedConfig;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->mutableConfig = $this->objectManager->get(MutableScopeConfigInterface::class);
        $this->block = $this->objectManager->get(LayoutInterface::class)->createBlock(Form::class);
    }

    /**
     * @param int $num
     * @param int $expected
     * @dataProvider getMaxInvitationsPerSendDataProvider
     * @return void
     */
    public function testGetMaxInvitationsPerSend($num, $expected): void
    {
        try {
            $this->changeConfig(Config::XML_PATH_MAX_INVITATION_AMOUNT_PER_SEND, $num);
            $this->assertEquals($expected, $this->block->getMaxInvitationsPerSend());
        } finally {
            $this->restoreConfig();
        }
    }

    /**
     * @return array
     */
    public function getMaxInvitationsPerSendDataProvider(): array
    {
        return [[1, 1], [3, 3], [100, 100], [0, 1]];
    }

    /**
     * @return void
     */
    public function testIsInvitationMessageAllowed(): void
    {
        try {
            $this->changeConfig(Config::XML_PATH_USE_INVITATION_MESSAGE, 1);
            $this->assertTrue($this->block->isInvitationMessageAllowed());

            $this->changeConfig(Config::XML_PATH_USE_INVITATION_MESSAGE, 0);
            $this->assertFalse($this->block->isInvitationMessageAllowed());
        } finally {
            $this->restoreConfig();
        }
    }

    /**
     * Sets new value to store config path, remembers old value
     *
     * @param  string $path
     * @param  int $value
     * @return void
     */
    private function changeConfig(string $path, int $value): void
    {
        $oldValue = $this->mutableConfig->getValue($path, ScopeInterface::SCOPE_STORE);
        $this->mutableConfig->setValue($path, $value, ScopeInterface::SCOPE_STORE);

        if (!$this->rememberedConfig) {
            $this->rememberedConfig = ['path' => $path, 'old_value' => $oldValue];
        }
    }

    /**
     * Restores previously remembered store config value
     *
     * @return void
     */
    private function restoreConfig(): void
    {
        $this->mutableConfig->setValue(
            $this->rememberedConfig['path'],
            $this->rememberedConfig['old_value'],
            ScopeInterface::SCOPE_STORE
        );
        $this->rememberedConfig = null;
    }
}
