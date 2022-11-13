<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Helper;

use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Checks reward points helper functionality
 *
 * @magentoDbIsolation enabled
 *
 * @see \Magento\Reward\Helper\Data
 */
class DataTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var Data */
    private $helper;

    /** @var MutableScopeConfigInterface */
    private $scopeConfig;

    /** @var SessionManagerInterface */
    private $session;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->helper = $this->objectManager->get(Data::class);
        $this->scopeConfig = $this->objectManager->get(MutableScopeConfigInterface::class);
        $this->session = $this->objectManager->get(SessionManagerInterface::class);
    }

    /**
     * @dataProvider configProvider
     *
     * @magentoAppIsolation enabled
     *
     * @param array $config
     * @param bool $expectedValue
     * @return void
     */
    public function testIsEnabledOnFront(array $config, bool $expectedValue): void
    {
        $this->prepareConfig($config);
        $this->assertEquals($expectedValue, $this->helper->isEnabledOnFront());
    }

    /**
     * @return array
     */
    public function configProvider(): array
    {
        return [
            'general_enabled_front_disabled' => [
                'config' => [
                    Data::XML_PATH_ENABLED => '1',
                    'magento_reward/general/is_enabled_on_front' => '0',
                ],
                'expected_value' => false,
            ],
            'general_disabled_front_enabled' => [
                'config' => [
                    Data::XML_PATH_ENABLED => '0',
                    'magento_reward/general/is_enabled_on_front' => '1',
                ],
                'expected_value' => false,
            ],
            'general_enabled_front_enabled' => [
                'config' => [
                    Data::XML_PATH_ENABLED => '1',
                    'magento_reward/general/is_enabled_on_front' => '1',
                ],
                'expected_value' => true,
            ],
            'general_disabled_front_disabled' => [
                'config' => [
                    Data::XML_PATH_ENABLED => '0',
                    'magento_reward/general/is_enabled_on_front' => '0',
                ],
                'expected_value' => false,
            ],
        ];
    }

    /**
     * @magentoConfigFixture current_store currency/options/allow USD,EUR
     *
     * @return void
     */
    public function testFormatAmount(): void
    {
        $this->session->setCurrencyCode('EUR');
        $this->assertEquals('€70.67', strip_tags($this->helper->formatAmount(100)));
    }

    /**
     * Prepare configuration
     *
     * @param array $config
     * @return void
     */
    private function prepareConfig(array $config): void
    {
        foreach ($config as $path => $value) {
            $path === 'magento_reward/general/is_enabled_on_front'
                ? $this->scopeConfig->setValue($path, $value, ScopeInterface::SCOPE_WEBSITE)
                : $this->scopeConfig->setValue($path, $value, ScopeInterface::SCOPE_STORE);
        }
    }
}
