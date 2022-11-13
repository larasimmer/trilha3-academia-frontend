<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardAccount\Model;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\CustomerBalance\Model\Balance;
use Magento\CustomerBalance\Model\BalanceFactory;
use Magento\CustomerBalance\Model\Balance\HistoryFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\GiftCardAccount\Api\GiftCardRedeemerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Test for gift card account redeemer model.
 *
 * @see \Magento\GiftCardAccount\Model\Redeemer
 *
 * @magentoDbIsolation enabled
 */
class RedeemerTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var GiftCardRedeemerInterface */
    private $giftCardRedeemer;

    /** @var BalanceFactory */
    private $balanceFactory;

    /** @var CustomerRepositoryInterface */
    private $customerRepository;

    /** @var HistoryFactory */
    private $balanceHistoryFactory;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->giftCardRedeemer = $this->objectManager->get(GiftCardRedeemerInterface::class);
        $this->balanceFactory = $this->objectManager->get(BalanceFactory::class);
        $this->customerRepository = $this->objectManager->get(CustomerRepositoryInterface::class);
        $this->balanceHistoryFactory = $this->objectManager->get(HistoryFactory::class);
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDataFixture Magento/GiftCardAccount/_files/giftcardaccount.php
     *
     * @return void
     */
    public function testRedeemGiftCard(): void
    {
        $customerId = 1;
        $giftCardCode = 'giftcardaccount_fixture';
        $customer = $this->customerRepository->get('customer@example.com');
        $this->giftCardRedeemer->redeem($giftCardCode, $customerId);
        $this->assertCustomerBalance($customer, 9.99);
        $historyData = $this->balanceHistoryFactory->create()->getHistoryData($customerId);
        $this->assertNotEmpty($historyData);
        $historyData = current($historyData);
        $this->assertEquals(
            (string)__('Gift Card Redeemed: %1. For customer #%2.', $giftCardCode, $customerId),
            $historyData['additional_info']
        );
    }

    /**
     * @magentoConfigFixture current_store customer/magento_customerbalance/is_enabled 0
     *
     * @return void
     */
    public function testRedeemWithDisabledCustomerBalance(): void
    {
        $this->expectExceptionObject(new CouldNotSaveException(__('You can\'t redeem a gift card now.')));
        $this->giftCardRedeemer->redeem('giftcardaccount_fixture', 1);
    }

    /**
     * @return void
     */
    public function testRedeemNotExistingGiftCardAccount(): void
    {
        $this->expectExceptionObject(new NoSuchEntityException(__('Gift card not found')));
        $this->giftCardRedeemer->redeem('not_existing_code', 1);
    }

    /**
     * @magentoDataFixture Magento/GiftCardAccount/_files/giftcardaccount.php
     *
     * @return void
     */
    public function testRedeemForNotExistingCustomer(): void
    {
        $this->expectExceptionObject(new CouldNotSaveException(__('Cannot find the customer to update balance')));
        $this->giftCardRedeemer->redeem('giftcardaccount_fixture', 989);
    }

    /**
     * @magentoDataFixture Magento/GiftCardAccount/_files/not_redeemable_gift_card_account.php
     *
     * @return void
     */
    public function testRedeemExpiredGiftCardAccount(): void
    {
        $this->expectExceptionObject(new CouldNotSaveException(__('Gift card account is not redeemable.')));
        $this->giftCardRedeemer->redeem('not_redeemable_gift_card_account', 1);
    }

    /**
     * Assert customer balance.
     *
     * @param CustomerInterface $customer
     * @param float $expectedAmount
     * @return void
     */
    private function assertCustomerBalance(CustomerInterface $customer, float $expectedAmount): void
    {
        $customerBalance = $this->loadCustomerBalance($customer);
        $this->assertEquals((int)$customer->getId(), (int)$customerBalance->getCustomerId());
        $this->assertEquals($expectedAmount, $customerBalance->getAmount());
    }

    /**
     * Load customer balance.
     *
     * @param CustomerInterface $customer
     * @return Balance
     */
    private function loadCustomerBalance(CustomerInterface $customer): Balance
    {
        $customerBalance = $this->balanceFactory->create();
        $customerBalance->setCustomerId($customer->getId());
        $customerBalance->setWebsiteId($customer->getWebsiteId());

        return $customerBalance->loadByCustomer();
    }
}
