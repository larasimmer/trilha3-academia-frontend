<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Block\Customer\Reward;

use Magento\Customer\Model\CustomerRegistry;
use Magento\Customer\Model\Session;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Checks reward points subscription displaying in account dashboard
 *
 * @see \Magento\Reward\Block\Customer\Reward\Subscription
 *
 * @magentoAppArea frontend
 * @magentoAppIsolation enabled
 */
class SubscriptionTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var LayoutInterface */
    private $layout;

    /** @var Session */
    private $customerSession;

    /** @var Subscription */
    private $block;

    /** @var CustomerRegistry  */
    private $customerRegistry;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->layout = $this->objectManager->get(LayoutInterface::class);
        $this->customerSession = $this->objectManager->get(Session::class);
        $this->block = $this->layout->createBlock(Subscription::class);
        $this->customerRegistry = $this->objectManager->get(CustomerRegistry::class);
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
     * @magentoDataFixture Magento/Reward/_files/customer_subscribed_for_update_notifications.php
     *
     * @return void
     */
    public function testIsSubscribedForUpdates(): void
    {
        $this->customerSession->loginById(1);
        $this->assertTrue($this->block->isSubscribedForUpdates());
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     *
     * @return void
     */
    public function testNotSubscribedForUpdates(): void
    {
        $this->customerSession->loginById(1);
        $this->assertFalse($this->block->isSubscribedForUpdates());
    }

    /**
     * @magentoDataFixture Magento/Reward/_files/customer_subscribed_for_warning_notifications.php
     *
     * @return void
     */
    public function testIsSubscribedForWarnings(): void
    {
        $customer = $this->customerRegistry->retrieveByEmail('customer@example.com');
        $this->customerSession->setCustomerAsLoggedIn($customer);
        $this->assertTrue($this->block->isSubscribedForWarnings());
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     *
     * @return void
     */
    public function testIsNotSubscribedForWarnings(): void
    {
        $this->customerSession->loginById(1);
        $this->assertFalse($this->block->isSubscribedForWarnings());
    }
}
