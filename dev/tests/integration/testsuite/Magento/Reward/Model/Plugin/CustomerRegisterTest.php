<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Model\Plugin;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\GroupManagementInterface;
use Magento\Customer\Model\AccountManagement;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Framework\Math\Random;
use Magento\Framework\ObjectManagerInterface;
use Magento\Reward\Model\Reward;
use Magento\Reward\Model\RewardFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Customer\Model\DeleteCustomer;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Interception\PluginList;
use PHPUnit\Framework\TestCase;

/**
 * Customer register plugin test
 *
 * @see \Magento\Reward\Model\Plugin\CustomerRegister
 *
 * @magentoAppArea frontend
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CustomerRegisterTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var AccountManagement */
    private $accountManagement;

    /** @var CustomerFactory */
    private $customerFactory;

    /** @var CustomerRepository */
    private $customerRepository;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var Random */
    private $random;

    /** @var MutableScopeConfigInterface */
    private $config;

    /** @var RewardFactory */
    private $rewardFactory;

    /** @var DeleteCustomer */
    private $deleteCustomer;

    /** @var GroupManagementInterface */
    private $groupManagement;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->accountManagement = $this->objectManager->get(AccountManagement::class);
        $this->customerFactory = $this->objectManager->get(CustomerFactory::class);
        $this->customerRepository = $this->objectManager->get(CustomerRepository::class);
        $this->random = $this->objectManager->get(Random::class);
        $this->storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $this->config = $this->objectManager->get(MutableScopeConfigInterface::class);
        $this->rewardFactory = $this->objectManager->get(RewardFactory::class);
        $this->deleteCustomer = $this->objectManager->get(DeleteCustomer::class);
        $this->groupManagement = $this->objectManager->get(GroupManagementInterface::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        $this->deleteCustomer->execute('newtestcustomer@example.com');

        parent::tearDown();
    }

    /**
     * Check, customer register plugin is registered for storefront.
     *
     * @magentoAppArea frontend
     * @return void
     */
    public function testCustomerRegisterIsRegistered(): void
    {
        $pluginInfo = Bootstrap::getObjectManager()->get(PluginList::class)->get(AccountManagement::class, []);
        $this->assertSame(CustomerRegister::class, $pluginInfo['customerRegister']['instance']);
    }

    /**
     * @return void
     */
    public function testCustomerRewards(): void
    {
        $this->config->setValue(
            'magento_reward/points/register',
            15,
            ScopeInterface::SCOPE_WEBSITE,
            'base'
        );
        $customer = $this->customerFactory->create();
        $customer->addData($this->getCustomerData());
        $this->accountManagement->createAccountWithPasswordHash(
            $customer->getDataModel(),
            $this->random->getUniqueHash()
        );
        $this->assertReward($customer->getEmail(), 15);
    }

    /**
     * Assert reward
     *
     * @param string $customerEmail
     * @param int $expectedPointsAmount
     * @return void
     */
    private function assertReward(string $customerEmail, int $expectedPointsAmount): void
    {
        $customer = $this->customerRepository->get($customerEmail);
        $reward = $this->loadRewardDataByCustomer($customer);
        $this->assertEquals((int)$customer->getId(), (int)$reward->getCustomerId());
        $this->assertEquals($expectedPointsAmount, (int)$reward->getPointsBalance());
    }

    /**
     * Load reward by customer
     *
     * @param CustomerInterface $customer
     * @return Reward
     */
    private function loadRewardDataByCustomer(CustomerInterface $customer): Reward
    {
        $reward = $this->rewardFactory->create();
        $reward->setCustomerId($customer->getId());
        $reward->setWebsiteId($customer->getWebsiteId());

        return $reward->loadByCustomer();
    }

    /**
     * Get customer data
     *
     * @return array
     */
    private function getCustomerData(): array
    {
        $store = $this->storeManager->getStore();
        $store->getWebsiteId();
        $storeId = $store->getId();
        return [
            CustomerInterface::GROUP_ID => $this->groupManagement->getDefaultGroup($storeId)->getId(),
            CustomerInterface::WEBSITE_ID => $store->getWebsiteId(),
            CustomerInterface::STORE_ID => $storeId,
            CustomerInterface::FIRSTNAME => 'test firstname',
            CustomerInterface::LASTNAME => 'test lastname',
            CustomerInterface::EMAIL => 'newtestcustomer@example.com',
            CustomerInterface::DEFAULT_BILLING => 1,
            CustomerInterface::DEFAULT_SHIPPING => 1,
        ];
    }
}
