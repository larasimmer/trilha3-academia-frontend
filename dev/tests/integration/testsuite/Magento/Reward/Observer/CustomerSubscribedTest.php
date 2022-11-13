<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Observer;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Newsletter\Model\Subscriber;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Reward\Model\Reward;
use Magento\Reward\Model\RewardFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Subscriber reward point observer test
 *
 * @see \Magento\Reward\Observer\CustomerSubscribed
 *
 * @magentoAppArea frontend
 * @magentoDbIsolation enabled
 */
class CustomerSubscribedTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var SubscriberFactory */
    private $subscriberFactory;

    /** @var ManagerInterface */
    private $eventManager;

    /** @var CustomerRepositoryInterface */
    private $customerRepository;

    /** @var RewardFactory */
    private $rewardFactory;

    /** @var MutableScopeConfigInterface */
    private $config;

    /** @var array */
    private $defaultConfig = [];

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->subscriberFactory = $this->objectManager->get(SubscriberFactory::class);
        $this->eventManager = $this->objectManager->get(ManagerInterface::class);
        $this->customerRepository = $this->objectManager->get(CustomerRepositoryInterface::class);
        $this->config = $this->objectManager->get(MutableScopeConfigInterface::class);
        $this->rewardFactory = $this->objectManager->get(RewardFactory::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        foreach ($this->defaultConfig as $path => $value) {
            $this->config->setValue($path, $value);
        }

        parent::tearDown();
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/new_customer.php
     *
     * @return void
     */
    public function testSubscriptionRewards(): void
    {
        $this->prepareConfig('magento_reward/points/newsletter', 20);
        $customer = $this->customerRepository->get('new_customer@example.com');
        $subscriber = $this->subscriberFactory->create(['data' => $this->getSubscriberData($customer)]);
        $this->eventManager->dispatch(
            'newsletter_subscriber_save_commit_after',
            ['data_object' => $subscriber, 'subscriber' => $subscriber]
        );
        $this->assertRewardPoints($customer, 20);
    }

    /**
     * Assert reward points
     *
     * @param CustomerInterface $customer
     * @param int $expectedPointsAmount
     * @return void
     */
    private function assertRewardPoints(CustomerInterface $customer, int $expectedPointsAmount): void
    {
        $reward = $this->loadRewardDataByCustomer($customer);
        $this->assertEquals((int)$customer->getId(), (int)$reward->getCustomerId());
        $this->assertEquals($expectedPointsAmount, (int)$reward->getPointsBalance());
    }

    /**
     * Load reward points data by customer email
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
     * Get subscriber data
     *
     * @param $customer
     * @return array
     */
    private function getSubscriberData($customer): array
    {
        return [
            'subscriber_status' => Subscriber::STATUS_SUBSCRIBED,
            'store_id' => $customer->getStoreId(),
            'customer_id' => $customer->getId(),
            'subscriber_emal' => $customer->getEmail(),
        ];
    }

    /**
     * Prepare configuration
     *
     * Need this method because there is no availability to set website scope config values
     *
     * @param string $path
     * @param int $value
     * @return void
     */
    private function prepareConfig(string $path, int $value): void
    {
        $this->defaultConfig[$path] = $this->config->getValue(
            $path,
            ScopeInterface::SCOPE_WEBSITE,
            'base'
        );
        $this->config->setValue($path, $value, ScopeInterface::SCOPE_WEBSITE, 'base');
    }
}
