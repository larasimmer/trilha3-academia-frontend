<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\GiftCardAccount\Api;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\CustomerBalance\Model\BalanceFactory;
use Magento\CustomerBalance\Model\Balance;
use Magento\Customer\Model\Session as CustomerSession;

/**
 * Test for Redeemer API.
 *
 * @magentoAppIsolation enabled
 */
class GiftCardRedeemerInterfaceTest extends TestCase
{
    /**
     * @var GiftCardRedeemerInterface
     */
    private $redeemer;

    /**
     * @var BalanceFactory
     */
    private $balanceFactory;

    /**
     * @var CustomerSession
     */
    private $loginManager;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        /** @var ObjectManager $objectManager */
        $objectManager = Bootstrap::getObjectManager();
        $this->redeemer = $objectManager->get(GiftCardRedeemerInterface::class);
        $this->balanceFactory = $objectManager->get(BalanceFactory::class);
        $this->loginManager = $objectManager->get(CustomerSession::class);
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        $this->loginManager->logout();
        $this->loginManager->clearStorage();
    }

    /**
     * Test positive case of redeeming a card.
     *
     * @magentoDataFixture Magento/GiftCardAccount/_files/giftcardaccount.php
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoConfigFixture default_store customer/magento_customerbalance/is_enabled 1
     */
    public function testRedeem()
    {
        $this->redeemer->redeem('giftcardaccount_fixture', 1);
        /** @var Balance $balance */
        $balance = $this->balanceFactory->create();
        $balance->setCustomerId(1);
        $balance->loadByCustomer();
        $this->assertEquals(9.99, $balance->getAmount());
    }

    /**
     * Case when code is invalid.
     *
     * @magentoConfigFixture default_store customer/magento_customerbalance/is_enabled 1
     */
    public function testRedeemWrongCode()
    {
        $this->expectException(\Magento\Framework\Exception\NoSuchEntityException::class);

        $this->redeemer->redeem('fake_code', 1);
    }

    /**
     * Case when customer is invalid.
     *
     * @magentoDataFixture Magento/GiftCardAccount/_files/giftcardaccount.php
     * @magentoConfigFixture default_store customer/magento_customerbalance/is_enabled 1
     */
    public function testRedeemInvalidCustomer()
    {
        $this->expectException(\Magento\Framework\Exception\CouldNotSaveException::class);

        $this->redeemer->redeem('giftcardaccount_fixture', 1);
    }

    /**
     * Case when same card used twice.
     *
     * @magentoDataFixture Magento/GiftCardAccount/_files/giftcardaccount.php
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoConfigFixture default_store customer/magento_customerbalance/is_enabled 1
     */
    public function testRedeemTwice()
    {
        $this->expectException(\Magento\Framework\Exception\NoSuchEntityException::class);

        try {
            $this->redeemer->redeem('giftcardaccount_fixture', 1);
        } catch (\Throwable $exception) {
            throw new \RuntimeException('First redeem did not work', 0, $exception);
        }
        $this->redeemer->redeem('giftcardaccount_fixture', 1);
    }

    /**
     * Case when there were too many attempts to use a card by code.
     *
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoConfigFixture default_store customer/captcha/enable 1
     * @magentoConfigFixture default_store customer/captcha/forms user_forgotpassword,user_login,gift_code_request
     * @magentoConfigFixture default_store customer/captcha/failed_attempts_login 1
     * @magentoConfigFixture default_store customer/captcha/failed_attempts_ip 1
     * @magentoConfigFixture default_store customer/magento_customerbalance/is_enabled 1
     */
    public function testRedeemTooMany()
    {
        $this->expectException(\Magento\GiftCardAccount\Api\Exception\TooManyAttemptsException::class);

        $this->loginManager->loginById(1);
        try {
            $this->redeemer->redeem('fake_code', 1);
        } catch (NoSuchEntityException $exception) {
            //The codes are fake.
        }
        $this->redeemer->redeem('fake_code', 1);
    }

    /**
     * Case when customer balance feature is disabled.
     *
     * @magentoConfigFixture default_store customer/magento_customerbalance/is_enabled 0
     * @magentoDataFixture Magento/GiftCardAccount/_files/giftcardaccount.php
     * @magentoDataFixture Magento/Customer/_files/customer.php
     */
    public function testRedeemBalanceDisabled()
    {
        $this->expectException(\Magento\Framework\Exception\CouldNotSaveException::class);


        $this->redeemer->redeem('giftcardaccount_fixture', 1);
    }
}
