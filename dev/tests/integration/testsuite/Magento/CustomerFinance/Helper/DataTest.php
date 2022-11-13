<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerFinance\Helper;

/**
 * Helper data test.
 */
class DataTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CustomerFinance\Helper\Data
     */
    protected $_customerFinanceHelper;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $_moduleManagerMock;

    /**
     * Set import/export helper
     *
     * @static
     */
    protected function setUp(): void
    {
        $this->_moduleManagerMock = $this->createMock(\Magento\Framework\Module\Manager::class);
        $context = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
            \Magento\Framework\App\Helper\Context::class,
            ['moduleManager' => $this->_moduleManagerMock]
        );
        $this->_customerFinanceHelper = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
            \Magento\CustomerFinance\Helper\Data::class,
            ['context' => $context]
        );
    }

    /**
     * Is reward points enabled in config - active/enabled
     *
     * @magentoConfigFixture current_store magento_reward/general/is_enabled  1
     */
    public function testIsRewardPointsEnabledActiveEnabled()
    {
        $this->_moduleManagerMock->expects(
            $this->any()
        )->method(
            'isEnabled'
        )->with(
            'Magento_Reward'
        )->willReturn(
            true
        );
        $this->assertTrue($this->_customerFinanceHelper->isRewardPointsEnabled());
    }

    /**
     * Is reward points enabled in config - active/disabled
     *
     * @magentoConfigFixture current_store magento_reward/general/is_enabled  0
     */
    public function testIsRewardPointsEnabledActiveDisabled()
    {
        $this->_moduleManagerMock->expects(
            $this->any()
        )->method(
            'isEnabled'
        )->with(
            'Magento_Reward'
        )->willReturn(
            true
        );
        $this->assertFalse($this->_customerFinanceHelper->isRewardPointsEnabled());
    }

    /**
     * Is reward points enabled in config - inactive/enabled
     *
     * @magentoConfigFixture current_store magento_reward/general/is_enabled  1
     */
    public function testIsRewardPointsEnabledInactiveEnabled()
    {
        $this->_moduleManagerMock->expects(
            $this->any()
        )->method(
            'isEnabled'
        )->with(
            'Magento_Reward'
        )->willReturn(
            null
        );
        $this->assertFalse($this->_customerFinanceHelper->isRewardPointsEnabled());
    }

    /**
     * Is reward points enabled in config - inactive/disabled
     *
     * @magentoConfigFixture current_store magento_reward/general/is_enabled  0
     */
    public function testIsRewardPointsEnabledInactiveDisabled()
    {
        $this->_moduleManagerMock->expects(
            $this->any()
        )->method(
            'isEnabled'
        )->with(
            'Magento_Reward'
        )->willReturn(
            null
        );
        $this->assertFalse($this->_customerFinanceHelper->isRewardPointsEnabled());
    }

    /**
     * Is customer balance enabled in config - active/enabled
     *
     * @magentoConfigFixture current_store customer/magento_customerbalance/is_enabled  1
     */
    public function testisCustomerBalanceEnabledActiveEnabled()
    {
        $this->_moduleManagerMock->expects(
            $this->any()
        )->method(
            'isEnabled'
        )->with(
            'Magento_CustomerBalance'
        )->willReturn(
            true
        );
        $this->assertTrue($this->_customerFinanceHelper->isCustomerBalanceEnabled());
    }

    /**
     * Is customer balance enabled in config - active/disabled
     *
     * @magentoConfigFixture current_store customer/magento_customerbalance/is_enabled  0
     */
    public function testisCustomerBalanceEnabledActiveDisabled()
    {
        $this->_moduleManagerMock->expects(
            $this->any()
        )->method(
            'isEnabled'
        )->with(
            'Magento_CustomerBalance'
        )->willReturn(
            true
        );
        $this->assertFalse($this->_customerFinanceHelper->isCustomerBalanceEnabled());
    }

    /**
     * Is customer balance enabled in config - inactive/enabled
     *
     * @magentoConfigFixture current_store customer/magento_customerbalance/is_enabled  1
     */
    public function testisCustomerBalanceEnabledInactiveEnabled()
    {
        $this->_moduleManagerMock->expects(
            $this->any()
        )->method(
            'isEnabled'
        )->with(
            'Magento_CustomerBalance'
        )->willReturn(
            null
        );
        $this->assertFalse($this->_customerFinanceHelper->isCustomerBalanceEnabled());
    }

    /**
     * Is customer balance enabled in config - inactive/disabled
     *
     * @magentoConfigFixture current_store customer/magento_customerbalance/is_enabled  0
     */
    public function testisCustomerBalanceEnabledInactiveDisabled()
    {
        $this->_moduleManagerMock->expects(
            $this->any()
        )->method(
            'isEnabled'
        )->with(
            'Magento_CustomerBalance'
        )->willReturn(
            null
        );
        $this->assertFalse($this->_customerFinanceHelper->isCustomerBalanceEnabled());
    }
}
