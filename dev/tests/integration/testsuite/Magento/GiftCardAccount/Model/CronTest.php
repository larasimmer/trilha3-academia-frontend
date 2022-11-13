<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardAccount\Model;

use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Test cron for correct functionality
 */
class CronTest extends TestCase
{
    /**
     * @var Cron
     */
    private $model;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->model = Bootstrap::getObjectManager()->create(Cron::class);
    }

    /**
     * Test that gift card accounts states are updated depending on their current states and expiration dates
     *
     * @magentoDataFixture Magento/GiftCardAccount/_files/giftcardaccounts_for_search.php
     * @magentoDataFixture Magento/GiftCardAccount/_files/expired_giftcard_account.php
     * @magentoDataFixture Magento/GiftCardAccount/_files/expired_giftcardaccount_with_future_date_expires.php
     * @magentoDataFixture Magento/GiftCardAccount/_files/giftcardaccount_with_past_date_expires.php
     */
    public function testUpdateStates()
    {
        $this->model->updateStates();
        $account = $this->getGiftCardAccount('gift_card_account_1');
        $this->assertEquals(Giftcardaccount::STATE_USED, $account->getState());
        $account = $this->getGiftCardAccount('gift_card_account_2');
        $this->assertEquals(Giftcardaccount::STATE_AVAILABLE, $account->getState());
        $account = $this->getGiftCardAccount('gift_card_account_3');
        $this->assertEquals(Giftcardaccount::STATE_USED, $account->getState());
        $account = $this->getGiftCardAccount('gift_card_account_4');
        $this->assertEquals(Giftcardaccount::STATE_REDEEMED, $account->getState());
        $account = $this->getGiftCardAccount('gift_card_account_5');
        $this->assertEquals(Giftcardaccount::STATE_AVAILABLE, $account->getState());
        $account = $this->getGiftCardAccount('expired_giftcard_account');
        $this->assertEquals(Giftcardaccount::STATE_EXPIRED, $account->getState());
        $account = $this->getGiftCardAccount('expired_giftcardaccount_with_future_date_expires');
        $this->assertEquals(Giftcardaccount::STATE_AVAILABLE, $account->getState());
        $account = $this->getGiftCardAccount('giftcardaccount_with_past_date_expires');
        $this->assertEquals(Giftcardaccount::STATE_EXPIRED, $account->getState());
    }

    /**
     * Get gift card account by code
     *
     * @param string $code
     * @return Giftcardaccount
     */
    private function getGiftCardAccount(string $code): Giftcardaccount
    {
        $objectManager = Bootstrap::getObjectManager();
        /** * @var Giftcardaccount $model */
        $model = $objectManager->create(Giftcardaccount::class);
        $model->loadByCode($code);
        return $model;
    }
}
