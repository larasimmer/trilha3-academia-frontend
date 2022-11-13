<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Block;

use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Class checks reward tooltip displaying
 *
 * @see \Magento\Reward\Block\Tooltip
 *
 *
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 * @magentoAppArea frontend
 */
class TooltipTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var Page */
    private $page;

    /** @var MutableScopeConfigInterface */
    private $config;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->page = $this->objectManager->get(PageFactory::class)->create();
        $this->config = $this->objectManager->get(MutableScopeConfigInterface::class);
    }

    /**
     * @dataProvider tooltipDataProvider
     *
     * @param string $configPath
     * @param string $configValue
     * @param string $blockName
     * @param string $expectedMessage
     * @return void
     */
    public function testTooltip(
        string $configPath,
        string $configValue,
        string $blockName,
        string $expectedMessage
    ): void {
        $this->config->setValue($configPath, $configValue, ScopeInterface::SCOPE_WEBSITE, 'base');
        $this->preparePage();
        $block = $this->page->getLayout()->getBlock($blockName);
        $this->assertNotFalse($block);
        $this->assertStringContainsString($expectedMessage, strip_tags($block->toHtml()));
    }

    /**
     * @return array
     */
    public function tooltipDataProvider(): array
    {
        return [
            'register_tooltip' => [
                'config_path' => 'magento_reward/points/register',
                'config_value' => '25',
                'block_name' => 'reward.tooltip.register',
                'expected_message' => (string)__('Create an account on our site now and earn 25 Reward points.')
            ],
            'subscription_tooltip' => [
                'config_path' => 'magento_reward/points/newsletter',
                'config_value' => '25',
                'block_name' => 'reward.tooltip.newsletter',
                'expected_message' => (string)__('Subscribe to our newsletter now and earn 25 Reward points.')
            ],
        ];
    }

    /**
     * @dataProvider emptyTooltipProvider
     *
     * @param string $blockName
     * @return void
     */
    public function testEmptyTooltip(string $blockName): void
    {
        $this->preparePage();
        $block = $this->page->getLayout()->getBlock($blockName);
        $this->assertNotFalse($block);
        $this->assertEmpty($block->toHtml());
    }

    /**
     * @return array
     */
    public function emptyTooltipProvider(): array
    {
        return [
            'registration_tooltip' => ['reward.tooltip.register'],
            'newsletter_tooltip' => ['reward.tooltip.newsletter'],
        ];
    }

    /**
     * Prepare page before render
     *
     * @return void
     */
    private function preparePage(): void
    {
        $this->page->addHandle([
            'default',
            'customer_account_create',
        ]);
        $this->page->getLayout()->generateXml();
    }
}
