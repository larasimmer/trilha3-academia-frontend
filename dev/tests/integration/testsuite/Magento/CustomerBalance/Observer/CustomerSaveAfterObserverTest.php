<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerBalance\Observer;

use Laminas\Stdlib\Parameters;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\CustomerBalance\Model\Balance;
use Magento\CustomerBalance\Model\BalanceFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Test for save customer balance observer.
 *
 * @magentoDbIsolation enabled
 */
class CustomerSaveAfterObserverTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var ManagerInterface */
    private $eventManager;

    /** @var RequestInterface */
    private $request;

    /** @var Parameters */
    private $parameters;

    /** @var CustomerRepositoryInterface */
    private $customerRepository;

    /** @var BalanceFactory */
    private $balanceFactory;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->eventManager = $this->objectManager->get(ManagerInterface::class);
        $this->request = $this->objectManager->get(RequestInterface::class);
        $this->parameters = $this->objectManager->get(Parameters::class);
        $this->customerRepository = $this->objectManager->get(CustomerRepositoryInterface::class);
        $this->balanceFactory = $this->objectManager->get(BalanceFactory::class);
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     *
     * @return void
     */
    public function testAddAmountForCustomer(): void
    {
        $customer = $this->customerRepository->get('customer@example.com');
        $this->parameters->set('customerbalance', ['amount_delta' => 80, 'comment' => 'Test']);
        $this->request->setPost($this->parameters);
        $this->eventManager->dispatch(
            'adminhtml_customer_save_after',
            ['customer' => $customer, 'request' => $this->request]
        );
        $this->assertCustomerBalance($customer, 80.00);
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
