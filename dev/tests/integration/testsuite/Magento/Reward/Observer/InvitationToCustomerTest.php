<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\ObjectManagerInterface;
use Magento\Invitation\Model\InvitationFactory;
use Magento\Reward\Model\Reward;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Invitation to customer conversion test
 */
class InvitationToCustomerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var InvitationFactory
     */
    private $invitationFactory;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->invitationFactory = $this->objectManager->create(InvitationFactory::class);
    }

    /**
     * Test reward points update after invitation to customer conversion
     *
     * @magentoDataFixture Magento/Customer/_files/customer_confirmation_config_enable.php
     * @magentoDataFixture Magento/Reward/_files/reward_points_config.php
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDataFixture Magento/Invitation/_files/invitation.php
     */
    public function testInvitationToCustomerRewardPointsUpdate()
    {
        $config = $this->objectManager->get(ScopeConfigInterface::class);
        $rewardPointsConfigValue = $config->getValue(
            'magento_reward/points/invitation_customer',
            ScopeInterface::SCOPE_WEBSITE,
            'base'
        );
        $invitation = $this->invitationFactory->create()->load(1, 'customer_id');

        $event = new Event(['invitation' => $invitation]);
        $eventObserver = new Observer(['event' => $event]);

        $rewardObserver = $this->objectManager->create(InvitationToCustomer::class);
        $rewardObserver->execute($eventObserver);

        $reward = $this->objectManager->create(Reward::class);
        $reward->setCustomerId($invitation->getCustomerId());
        $websiteId = $this->objectManager
            ->get(StoreManagerInterface::class)->getStore()->getWebsiteId();
        $reward->setWebsiteId($websiteId);
        $reward->loadByCustomer();
        $this->assertEquals($reward->getPointsBalance(), $rewardPointsConfigValue);
    }
}
