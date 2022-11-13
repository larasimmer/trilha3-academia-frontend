<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftCardAccount\Model\Backend;

use Magento\TestFramework\Helper\Bootstrap;

/**
 * Test class for \Magento\GiftCardAccount\Model\Backend\History.
 * @magentoAppArea adminhtml
 * @magentoDataFixture Magento/GiftCardAccount/_files/giftcardaccount.php
 */
class HistoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var History
     */
    protected $_model;

    /**
     * @var \Magento\GiftCardAccount\Model\Giftcardaccount
     */
    protected $_giftcardAccount;

    protected function setUp(): void
    {
        Bootstrap::getObjectManager()->get(
            \Magento\Backend\Model\Auth\Session::class
        )->setUser(
            new \Magento\Framework\DataObject(['id' => 1, 'username' => 'Admin user'])
        );
        $this->_giftcardAccount = Bootstrap::getObjectManager()->create(
            \Magento\GiftCardAccount\Model\Giftcardaccount::class
        );
        $this->_giftcardAccount->loadByCode('giftcardaccount_fixture');
        $this->_model = Bootstrap::getObjectManager()->create(
            \Magento\GiftCardAccount\Model\History::class
        );
        $this->_model->setGiftcardaccount($this->_giftcardAccount);
    }

    /**
     * @covers \Magento\GiftCardAccount\Model\Backend\History::_getCreatedAdditionalInfo
     */
    public function testCreatedAdditionalInfo()
    {
        $this->_giftcardAccount->setHistoryAction(\Magento\GiftCardAccount\Model\History::ACTION_CREATED);
        $this->_model->save();
        $this->assertEquals(__('By admin: %1.', 'Admin user'), $this->_model->getAdditionalInfo());
    }

    /**
     * @covers \Magento\GiftCardAccount\Model\Backend\History::_getUpdatedAdditionalInfo
     */
    public function testUpdatedAdditionalInfo()
    {
        $this->_giftcardAccount->setHistoryAction(\Magento\GiftCardAccount\Model\History::ACTION_UPDATED);
        $this->_model->save();
        $this->assertEquals(__('By admin: %1.', 'Admin user'), $this->_model->getAdditionalInfo());
    }

    /**
     * @param string $recipientName
     * @dataProvider recipientDataProvider
     * @covers \Magento\GiftCardAccount\Model\Backend\History::_getSentAdditionalInfo
     */
    public function testSentAdditionalInfo($recipientName)
    {
        $recipientEmail = 'email@example.com';
        $this->_giftcardAccount->setRecipientEmail($recipientEmail);
        $this->_giftcardAccount->setRecipientName($recipientName);
        $this->_giftcardAccount->setHistoryAction(\Magento\GiftCardAccount\Model\History::ACTION_SENT);
        $this->_model->save();
        if ($recipientName) {
            $this->assertEquals(
                __('Recipient: %1.', "{$recipientName} <{$recipientEmail}>") . ' ' . __('By admin: %1.', 'Admin user'),
                $this->_model->getAdditionalInfo()
            );
        } else {
            $this->assertEquals(
                __('Recipient: %1.', $recipientEmail) . ' ' . __('By admin: %1.', 'Admin user'),
                $this->_model->getAdditionalInfo()
            );
        }
    }

    public function testSetAction()
    {
        $this->_giftcardAccount->setHistoryAction(\Magento\GiftCardAccount\Model\History::ACTION_CREATED);
        $this->_model->save();
        $this->assertEquals(\Magento\GiftCardAccount\Model\History::ACTION_CREATED, $this->_model->getAction());

        $this->_giftcardAccount->setHistoryAction(\Magento\GiftCardAccount\Model\History::ACTION_SENT);
        $this->_model->save();
        $this->assertEquals(\Magento\GiftCardAccount\Model\History::ACTION_SENT, $this->_model->getAction());

        $this->_giftcardAccount->setHistoryAction(\Magento\GiftCardAccount\Model\History::ACTION_USED);
        $this->_model->save();
        $this->assertEquals(\Magento\GiftCardAccount\Model\History::ACTION_USED, $this->_model->getAction());
    }

    public function recipientDataProvider()
    {
        return [[null], ['recipient']];
    }
}
