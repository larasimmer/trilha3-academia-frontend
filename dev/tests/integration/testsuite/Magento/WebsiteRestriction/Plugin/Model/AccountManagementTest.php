<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\WebsiteRestriction\Plugin\Model;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\GroupManagementInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Magento\Framework\ObjectManagerInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Api\AccountManagementInterface;

/**
 * Class for test plugin Account Management with website restrictions
 */
class AccountManagementTest extends TestCase
{
    /** @var CustomerRepositoryInterface */
    private $customerRepository;

    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var CustomerInterfaceFactory */
    private $customerFactory;

    /** @var AccountManagementInterface */
    private $accountManagement;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var GroupManagementInterface */
    private $groupManagement;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->customerRepository = $this->objectManager->get(CustomerRepositoryInterface::class);
        $this->customerFactory = $this->objectManager->create(CustomerInterfaceFactory::class);
        $this->accountManagement = $this->objectManager->get(AccountManagementInterface::class);
        $this->storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $this->groupManagement = $this->objectManager->get(GroupManagementInterface::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        try {
            $customer = $this->customerRepository->get('email321@example.com');
            $this->customerRepository->delete($customer);
            // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock
        } catch (NoSuchEntityException $exception) {
        }
    }

    /**
     * Assure that customer can be saved on backend after web restrictions is turned on
     *
     * @return void
     * @magentoConfigFixture current_store general/restriction/is_active 1
     * @magentoConfigFixture current_store general/restriction/mode 1
     * @magentoAppArea adminhtml
     */
    public function testBackendCreateCustomerWithWebRestrictionOn(): void
    {
        $storeId = $this->storeManager->getDefaultStoreView()->getId();
        $groupId = $this->groupManagement->getDefaultGroup()->getId();

        $newCustomerEntity = $this->customerFactory->create()
            ->setStoreId($storeId)
            ->setEmail('email321@example.com')
            ->setFirstname('FirstName')
            ->setLastname('LastName')
            ->setGroupId($groupId);
        $savedCustomer = $this->accountManagement->createAccount($newCustomerEntity, '_aPassword1');

        $this->assertNotNull($savedCustomer->getId());
    }
}
