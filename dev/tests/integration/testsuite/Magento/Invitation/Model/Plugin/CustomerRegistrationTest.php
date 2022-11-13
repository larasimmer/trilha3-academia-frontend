<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Invitation\Model\Plugin;

use Magento\Customer\Model\Registration;
use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\TestCase;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Interception\PluginList;

/**
 * Tests for customer register link plugin.
 *
 * @see \Magento\Invitation\Model\Plugin\CustomerRegistration
 *
 * @magentoAppArea frontend
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class CustomerRegistrationTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var Registration
     */
    private $registrationModel;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->registrationModel = $this->objectManager->get(Registration::class);
    }

    /**
     * Checks that invitation plugin is registered for storefront.
     *
     * @return void
     */
    public function testPluginIsRegistered(): void
    {
        $pluginInfo = $this->objectManager->get(PluginList::class)->get(Registration::class, []);
        $this->assertSame(
            CustomerRegistration::class,
            $pluginInfo['invitation_customer_registration_plugin']['instance']
        );
    }

    /**
     * @magentoConfigFixture current_store magento_invitation/general/enabled 0
     * @magentoConfigFixture current_store magento_invitation/general/registration_required_invitation 0
     *
     * @return void
     */
    public function testIsAllowedWithEnabledConfig(): void
    {
        $this->assertTrue($this->registrationModel->isAllowed());
    }

    /**
     * @magentoConfigFixture current_store magento_invitation/general/enabled 1
     * @magentoConfigFixture current_store magento_invitation/general/registration_required_invitation 1
     *
     * @return void
     */
    public function testIsAllowedWithDisabledConfig(): void
    {
        $this->assertFalse($this->registrationModel->isAllowed());
    }
}
