<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerBalance\Model\Balance;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\CustomerBalance\Model\Balance;
use Magento\CustomerBalance\Model\BalanceFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Mail\Template\TransportBuilderMock;
use PHPUnit\Framework\TestCase;

/**
 * Test for customer balance history model.
 *
 * @see \Magento\CustomerBalance\Model\Balance\History
 *
 * @magentoDbIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class HistoryTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var BalanceFactory */
    private $balanceFactory;

    /** @var CustomerRepositoryInterface */
    private $customerRepository;

    /** @var HistoryFactory */
    private $historyFactory;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var TransportBuilderMock */
    private $transportBuilder;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->customerRepository = $this->objectManager->get(CustomerRepositoryInterface::class);
        $this->balanceFactory = $this->objectManager->get(BalanceFactory::class);
        $this->historyFactory = $this->objectManager->get(HistoryFactory::class);
        $this->storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $this->transportBuilder = $this->objectManager->get(TransportBuilderMock::class);
    }

    /**
     * @magentoDataFixture Magento/CustomerBalance/_files/customer_balance_default_website.php
     *
     * @return void
     */
    public function testSaveHistoryWithActionUpdate(): void
    {
        $customer = $this->customerRepository->get('customer@example.com');
        $balance = $this->loadCustomerBalance($customer);
        $balance->setHistoryAction(History::ACTION_UPDATED);
        $history = $this->historyFactory->create();
        $history->setBalanceModel($balance);
        $history->beforeSave();
        $this->assertEquals(History::ACTION_UPDATED, $history->getAction());
        $this->assertEquals($balance->getId(), $history->getBalanceId());
        $this->assertEquals($balance->getAmount(), $history->getBalanceAmount());
    }

    /**
     * @magentoDataFixture Magento/CustomerBalance/_files/customer_balance_default_website.php
     *
     * @return void
     */
    public function testSaveHistoryWithoutAction(): void
    {
        $customer = $this->customerRepository->get('customer@example.com');
        $balance = $this->loadCustomerBalance($customer);
        $history = $this->historyFactory->create();
        $history->setBalanceModel($balance);
        $this->expectExceptionObject(
            new LocalizedException(__('The balance history action code is unknown. Verify the code and try again.'))
        );
        $history->beforeSave();
    }

    /**
     * @return void
     */
    public function testSaveHistoryWithoutBalance(): void
    {
        $history = $this->historyFactory->create();
        $this->expectExceptionObject(new LocalizedException(__('A balance is needed to save a balance history.')));
        $history->beforeSave();
    }

    /**
     * @return void
     */
    public function testSaveHistoryWithEmptyBalance(): void
    {
        $balance = $this->balanceFactory->create();
        $history = $this->historyFactory->create();
        $history->setBalanceModel($balance);
        $this->expectExceptionObject(new LocalizedException(__('A balance is needed to save a balance history.')));
        $history->beforeSave();
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     *
     * @return void
     */
    public function testSendMessageForCustomer(): void
    {
        $store = $this->storeManager->getStore('default');
        $customer = $this->customerRepository->get('customer@example.com');
        $balance = $this->balanceFactory->create();
        $balance->setCustomer($customer);
        $balance->setAmount(50);
        $balance->setNotifyByEmail(true, $store->getId());
        $history = $this->historyFactory->create();
        $history->setBalanceModel($balance);
        $history->afterSave();
        $this->assertTrue($history->getIsCustomerNotified());
        $expectedText = (string)__(
            'Your Store Credit balance is now %1.',
            $store->getBaseCurrency()->format(50, [], false)
        );
        $this->assertEmailMessage($expectedText);
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

    /**
     * Assert message.
     *
     * @param string $expectedText
     * @return void
     */
    private function assertEmailMessage(string $expectedText): void
    {
        $message = $this->transportBuilder->getSentMessage();
        $this->assertNotNull($message);
        $this->assertStringContainsString(
            $expectedText,
            $message->getBody()->getParts()[0]->getRawContent()
        );
    }
}
