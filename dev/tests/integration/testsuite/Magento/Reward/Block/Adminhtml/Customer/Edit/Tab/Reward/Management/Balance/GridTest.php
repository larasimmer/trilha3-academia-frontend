<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Block\Adminhtml\Customer\Edit\Tab\Reward\Management\Balance;

use Magento\Customer\Controller\RegistryConstants;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\LayoutInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Checks customers reward points grid in admin panel
 *
 * @see \Magento\Reward\Block\Adminhtml\Customer\Edit\Tab\Reward\Management\Balance\Grid
 *
 * @magentoAppArea adminhtml
 */
class GridTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var Grid */
    private $block;

    /** @var Registry */
    private $registry;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->block = $this->objectManager->get(LayoutInterface::class)->createBlock(Grid::class);
        $this->registry = $this->objectManager->get(Registry::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        $this->registry->unregister(RegistryConstants::CURRENT_CUSTOMER_ID);

        parent::tearDown();
    }

    /**
     * @magentoDataFixture Magento/Reward/_files/reward.php
     *
     * @return void
     */
    public function testBalanceGrid(): void
    {
        $this->registerCustomerId(1);
        $this->assertCount(1, $this->block->getPreparedCollection());
    }

    /**
     * Register customer id
     *
     * @param int $customerId
     * @return void
     */
    private function registerCustomerId(int $customerId): void
    {
        $this->registry->unregister(RegistryConstants::CURRENT_CUSTOMER_ID);
        $this->registry->register(RegistryConstants::CURRENT_CUSTOMER_ID, $customerId);
    }
}
