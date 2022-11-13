<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Reward;

use Magento\Customer\Model\Session;
use Magento\TestFramework\Helper\Bootstrap;

class InvitationTooltipTest extends \Magento\TestFramework\TestCase\AbstractController
{
    /**
     * Checks that reward point tooltips contain proper numbers.
     *
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDataFixture Magento/Reward/_files/reward_points_config.php
     */
    public function testInvitationRewardPointsTooltip()
    {
        $customerId = 1;
        $invitationCustomerPoints = 10;
        $invitationOrderPoints = 5;

        $this->login($customerId);
        $this->dispatch('invitation/index/index');

        $body = $this->getResponse()->getBody();
        $this->assertStringContainsString(
            "Send this invitation now and earn <strong>$invitationCustomerPoints</strong>",
            $body
        );
        $this->assertStringContainsString(
            "Earn <strong>$invitationOrderPoints</strong> Reward points for purchases your invitees make",
            $body
        );
    }

    /**
     * Login the user
     *
     * @param int $customerId Customer to mark as logged in for the session
     * @return void
     */
    private function login(int $customerId)
    {
        /** @var Session $session */
        $session = Bootstrap::getObjectManager()->get(Session::class);
        $session->loginById($customerId);
    }
}
