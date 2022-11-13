<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Controller\Adminhtml\Rma;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Rma\Model\ResourceModel\Rma\Grid\Collection as RmaCollection;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * 'RMA grid' Controller integration tests.
 *
 * @magentoAppArea adminhtml
 */
class RmaCustomerTest extends AbstractBackendController
{
    /**
     * @var LayoutInterface
     */
    private $layout;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var string
     */
    private $rmaBlockName = 'adminhtml\customer\edit\tab\rma_0';

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->layout = $this->_objectManager->get(LayoutInterface::class);
        $this->customerRepository = $this->_objectManager->get(CustomerRepositoryInterface::class);
    }

    /**
     * Check RMA grid collection is prepared correctly for Customer with returns.
     *
     * @return void
     * @magentoDataFixture Magento/Rma/_files/rma_with_customer.php
     */
    public function testPrepareGridForCustomerWithReturns(): void
    {
        $this->dispatchRequestWithCustomer('customer_uk_address@test.com');
        /** @var RmaCollection $rmaCollection */
        $rmaCollection = $this->layout->getBlock($this->rmaBlockName)->getCollection();
        $this->assertCount(1, $rmaCollection);
    }

    /**
     * Check RMA grid collection is empty for Customer without returns.
     *
     * @return void
     * @magentoDataFixture Magento/Customer/_files/customer.php
     */
    public function testPrepareGridForCustomerWithoutReturns(): void
    {
        $this->dispatchRequestWithCustomer('customer@example.com');
        /** @var RmaCollection $rmaCollection */
        $rmaCollection = $this->layout->getBlock($this->rmaBlockName)->getCollection();
        $this->assertEmpty($rmaCollection);
    }

    /**
     * Dispatch request with provided Customer.
     *
     * @param string $email
     * @return void
     */
    private function dispatchRequestWithCustomer(string $email): void
    {
        $customer = $this->customerRepository->get($email);
        $this->getRequest()->setParams(['id' => $customer->getId()]);
        $this->dispatch('backend/admin/rma/rmaCustomer');
    }
}
