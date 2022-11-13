<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Block\Customer\Reward;

use Magento\Customer\Model\Session;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Checks reward points history displaying in account dashboard
 *
 * @see \Magento\Reward\Block\Customer\Reward\History
 *
 * @magentoAppArea frontend
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
class HistoryTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var LayoutInterface */
    private $layout;

    /** @var Session */
    private $customerSession;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->layout = $this->objectManager->get(LayoutInterface::class);
        $this->customerSession = $this->objectManager->get(Session::class);
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
     * @magentoDataFixture Magento/Customer/_files/customer.php
     *
     * @return void
     */
    public function testGetEmptyHistory(): void
    {
        $this->customerSession->loginById(1);
        $this->assertFalse($this->createBlock()->getHistory());
    }

    /**
     * @magentoDataFixture Magento/Reward/_files/history.php
     *
     * @return void
     */
    public function testGetHistory(): void
    {
        $this->customerSession->loginById(1);
        $this->assertCount(1, $this->createBlock()->getHistory());
    }

    /**
     * @magentoConfigFixture current_store magento_reward/general/publish_history 0
     *
     * @return void
     */
    public function testEmptyHtml(): void
    {
        $this->assertEmpty($this->createBlock()->toHtml());
    }

    /**
     * Create history block
     *
     * @return History
     */
    private function createBlock(): History
    {
        $block = $this->objectManager->create(History::class);
        $block->setTemplate('Magento_Reward::customer/reward/history.phtml');
        $this->layout->addBlock($block, 'customer.reward.history');

        return $block;
    }
}
