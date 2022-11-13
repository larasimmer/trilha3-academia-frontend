<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerBalance\Model;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\CustomerBalance\Model\Balance\History;
use Magento\CustomerBalance\Model\ResourceModel\Balance as BalanceResource;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Test for customer balance model.
 *
 * @see \Magento\CustomerBalance\Model\Balance
 *
 * @magentoDbIsolation enabled
 */
class BalanceTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var BalanceFactory */
    private $balanceFactory;

    /** @var CustomerRepositoryInterface */
    private $customerRepository;

    /** @var BalanceResource */
    private $balanceResorce;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->customerRepository = $this->objectManager->get(CustomerRepositoryInterface::class);
        $this->balanceFactory = $this->objectManager->get(BalanceFactory::class);
        $this->balanceResorce = $this->objectManager->get(BalanceResource::class);
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     *
     * @return void
     */
    public function testAddAmountForCustomer(): void
    {
        $customer = $this->customerRepository->get('customer@example.com');
        $this->saveCustomerBalanceAmount($customer, 25);
        $expectedData = ['amount' => 25, 'customer_id' => $customer->getId()];
        $this->assertCustomerBalanceData($customer, $expectedData);
    }

    /**
     * @magentoDataFixture Magento/CustomerBalance/_files/customer_balance_default_website.php
     *
     * @return void
     */
    public function testUpdateAmountForCustomer(): void
    {
        $customer = $this->customerRepository->get('customer@example.com');
        $this->saveCustomerBalanceAmount($customer, -15);
        $expectedData = ['amount' => 35, 'customer_id' => $customer->getId()];
        $this->assertCustomerBalanceData($customer, $expectedData);
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     *
     * @return void
     */
    public function testHistoryActionCreated(): void
    {
        $customer = $this->customerRepository->get('customer@example.com');
        $balance = $this->prepareCustomerBalance($customer);
        $balance->beforeSave();
        $this->assertEquals(History::ACTION_CREATED, $balance->getHistoryAction());
    }

    /**
     * @magentoDataFixture Magento/CustomerBalance/_files/customer_balance_default_website.php
     *
     * @return void
     */
    public function testHistoryActionUpdated(): void
    {
        $customer = $this->customerRepository->get('customer@example.com');
        $balance = $this->prepareCustomerBalance($customer);
        $balance->beforeSave();
        $this->assertEquals(History::ACTION_UPDATED, $balance->getHistoryAction());
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     *
     * @return void
     */
    public function testTryToSaveForAdminWebsiteId(): void
    {
        $customer = $this->customerRepository->get('customer@example.com');
        $balance = $this->balanceFactory->create();
        $balance->setCustomer($customer);
        $balance->setWebsiteId(0);
        $this->expectExceptionObject(new LocalizedException(__('Please set a website ID.')));
        $balance->beforeSave();
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     *
     * @return void
     */
    public function testSaveWithoutStoreId(): void
    {
        $customer = $this->customerRepository->get('customer@example.com');
        $balance = $this->prepareCustomerBalance($customer);
        $balance->setAmountDelta(10);
        $balance->setData('notify_by_email', true);
        $this->expectExceptionObject(
            new LocalizedException(__('Please enter a store ID to send email notifications.'))
        );
        $balance->beforeSave();
    }

    /**
     * @magentoDataFixture Magento/CustomerBalance/_files/customer_balance_without_website_id.php
     *
     * @return void
     */
    public function testDeleteOrphanBalances(): void
    {
        $customerId = 1;
        $this->balanceFactory->create()->deleteBalancesByCustomerId($customerId);
        $this->assertEquals(0, $this->balanceFactory->create()->getOrphanBalancesCount($customerId));
    }

    /**
     * Save customer balance amount.
     *
     * @param CustomerInterface $customer
     * @param float $amount
     * @return void
     */
    private function saveCustomerBalanceAmount(CustomerInterface $customer, float $amount): void
    {
        $balance = $this->prepareCustomerBalance($customer);
        $balance->setAmountDelta($amount);
        $this->balanceResorce->save($balance);
    }

    /**
     * Assert customer balance data.
     *
     * @param CustomerInterface $customer
     * @param array $expectedData
     * @return void
     */
    private function assertCustomerBalanceData(CustomerInterface $customer, array $expectedData): void
    {
        $customerBalance = $this->prepareCustomerBalance($customer)->loadByCustomer();
        $this->assertEquals($expectedData['amount'], $customerBalance->getAmount());
        $this->assertEquals($expectedData['customer_id'], $customerBalance->getCustomerId());
    }

    /**
     * Prepare customer balance.
     *
     * @param CustomerInterface $customer
     * @return Balance
     */
    private function prepareCustomerBalance(CustomerInterface $customer): Balance
    {
        $customerBalance = $this->balanceFactory->create();
        $customerBalance->setCustomer($customer);
        $customerBalance->setWebsiteId($customer->getWebsiteId());

        return $customerBalance;
    }
}
