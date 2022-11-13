<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerBalance\Model\Adminhtml\Balance;

use Magento\TestFramework\Helper\Bootstrap;

/**
 * Test class for \Magento\CustomerBalance\Model\Adminhtml\Balance\History.
 * @magentoAppArea adminhtml
 * @magentoDataFixture Magento/CustomerBalance/_files/history.php
 */
class HistoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CustomerBalance\Model\Balance
     */
    protected $_balance;

    /**
     * @var \Magento\CustomerBalance\Model\Balance\History
     */
    protected $_model;

    protected function setUp(): void
    {
        Bootstrap::getObjectManager()->get(
            \Magento\Backend\Model\Auth\Session::class
        )->setUser(
            new \Magento\Framework\DataObject(['id' => 1, 'username' => 'Admin user'])
        );
        $websiteId = Bootstrap::getObjectManager()->get(
            \Magento\Store\Model\StoreManagerInterface::class
        )->getStore()->getWebsiteId();
        $customer = Bootstrap::getObjectManager()->create(
            \Magento\Customer\Model\Customer::class
        )->setWebsiteId(
            $websiteId
        )->loadByEmail(
            'customer@example.com'
        );
        $this->_balance = Bootstrap::getObjectManager()->create(
            \Magento\CustomerBalance\Model\Balance::class
        )->setCustomer(
            $customer
        )->loadByCustomer();
        $this->_model = Bootstrap::getObjectManager()->create(
            \Magento\CustomerBalance\Model\Balance\History::class
        )->setCustomerId(
            $customer->getId()
        )->setWebsiteId(
            $websiteId
        )->setBalanceModel(
            $this->_balance
        );
    }

    /**
     * @param null|string $comment
     * @dataProvider additionalInfoDataProvider
     * @covers \Magento\CustomerBalance\Model\Adminhtml\Balance\History::beforeSave
     */
    public function testAdditionalInfo($comment)
    {
        $this->_balance->setHistoryAction(
            \Magento\CustomerBalance\Model\Balance\History::ACTION_UPDATED
        )->unsUpdatedActionAdditionalInfo()->setComment(
            $comment
        );
        $this->_model->save();
        $expected = isset(
            $comment
        ) ? __(
            'By admin: %1. (%2)',
            'Admin user',
            $comment
        ) : __(
            'By admin: %1.',
            'Admin user'
        );
        $this->assertEquals($expected, $this->_model->getAdditionalInfo());
    }

    public function additionalInfoDataProvider()
    {
        return [['some comment'], [null]];
    }
}
