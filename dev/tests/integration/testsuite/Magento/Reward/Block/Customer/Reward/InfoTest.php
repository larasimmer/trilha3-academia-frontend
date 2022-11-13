<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Block\Customer\Reward;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Reward\Model\System\Config\Backend\Expiration;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Checks reward points info displaying in account dashboard
 *
 * @see \Magento\Reward\Block\Customer\Reward\Info
 *
 * @magentoAppArea frontend
 * @magentoDbIsolation enabled
 */
class InfoTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var LayoutInterface */
    private $layout;

    /** @var Info */
    private $block;

    /** @var Session */
    private $customerSession;

    /** @var MutableScopeConfigInterface */
    private $config;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->layout = $this->objectManager->get(LayoutInterface::class);
        $this->block = $this->layout->createBlock(Info::class);
        $this->block->setTemplate('Magento_Reward::customer/reward/info.phtml');
        $this->customerSession = $this->objectManager->get(Session::class);
        $this->config = $this->objectManager->get(MutableScopeConfigInterface::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        $this->customerSession->logout();

        parent::tearDown();
    }

    /**
     * @return void
     */
    public function testEmptyHtml(): void
    {
        $this->assertEmpty($this->block->toHtml());
    }

    /**
     * @magentoDataFixture Magento/Reward/_files/reward_points.php
     * @magentoDataFixture Magento/Reward/_files/rate.php
     *
     * @magentoAppIsolation enabled
     *
     * @return void
     */
    public function testNotEmptyHtml(): void
    {
        $expectedBlockData = [
            'points_balance' => '200',
            'currency_balance' => 2.0,
            'pts_to_amount_rate_pts' => 100,
            'pts_to_amount_rate_amount' => '1.0000',
            'amount_to_pts_rate_amount' => null,
            'amount_to_pts_rate_pts' => 0,
            'max_balance' => 1500,
            'is_max_balance_reached' => false,
            'min_balance' => 0,
            'is_min_balance_reached' => true,
            'expire_in' => 30,
            'is_history_published' => 1,
        ];
        $config = [
            'magento_reward/general/max_points_balance' => 1500,
            Expiration::XML_PATH_EXPIRATION_DAYS => 30,
        ];
        $this->prepareConfig($config);
        $this->customerSession->loginById(1);
        $this->assertStringContainsString((string)__('Reward points balance Information'), $this->block->toHtml());
        foreach ($expectedBlockData as $key => $value) {
            $this->assertEquals(
                $value,
                $this->block->getData($key),
                sprintf('Actual %s value does not match epected value', $key)
            );
        }
    }

    /**
     * Prepare configurations
     *
     * @param array $config
     * @return void
     */
    private function prepareConfig(array $config): void
    {
        foreach ($config as $path => $value) {
            $this->config->setValue($path, $value, ScopeInterface::SCOPE_WEBSITE, 'base');
        }
    }
}
