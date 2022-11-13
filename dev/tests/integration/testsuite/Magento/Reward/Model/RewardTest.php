<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Model;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Reward\Model\ResourceModel\Reward\History as HistoryResource;
use Magento\Reward\Model\Reward\HistoryFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Mail\Template\TransportBuilderMock;
use PHPUnit\Framework\TestCase;
use Magento\TestFramework\Helper\Bootstrap;

/**
 *  Reward model tests
 *
 * @see \Magento\Reward\Model\Reward
 */
class RewardTest extends TestCase
{
    /** @var ObjectManagerInterface  */
    private $objectManager;

    /** @var TransportBuilderMock */
    private $transportBuilder;

    /** @var CustomerRepositoryInterface */
    private $customerRepository;

    /** @var RewardFactory */
    private $rewardFactory;

    /** @var HistoryResource */
    private $historyResource;

    /** @var HistoryFactory */
    private $historyFactory;

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
        $this->transportBuilder = $this->objectManager->get(TransportBuilderMock::class);
        $this->rewardFactory = $this->objectManager->get(RewardFactory::class);
        $this->customerRepository = $this->objectManager->get(CustomerRepositoryInterface::class);
        $this->historyResource = $this->objectManager->get(HistoryResource::class);
        $this->historyFactory = $this->objectManager->get(HistoryFactory::class);
        $this->config = $this->objectManager->get(MutableScopeConfigInterface::class);
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
     * Test reward update notification functionality
     *
     * @magentoDataFixture Magento/Customer/_files/customer.php
     *
     * @return void
     */
    public function testBalanceUpdateNotificationIsNotSent(): void
    {
        $rewardModel = $this->prepareRewardToMessageSend('customer@example.com', 0, 100);
        $rewardModel->sendBalanceUpdateNotification();
        $this->assertNull($rewardModel->getData('balance_update_sent'));
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     *
     * @return void
     */
    public function testBalanceUpdateNotificationSent(): void
    {
        $rewardModel = $this->prepareRewardToMessageSend('customer@example.com', 1, 100);
        $rewardModel->sendBalanceUpdateNotification();
        $this->assertTrue($rewardModel->getData('balance_update_sent'));
        $message = $this->transportBuilder->getSentMessage();
        $this->assertNotNull($message);
        $this->assertStringContainsString(
            'You have 100 points that may be used in our store',
            $message->getBody()->getParts()[0]->getRawContent()
        );
    }

    /**
     * @magentoDataFixture Magento/Reward/_files/reward.php
     *
     * @return void
     */
    public function testHistorySave(): void
    {
        $reward = $this->loadRewardDataByCustomerEmail('customer@example.com');
        $reward->setPointsDelta(100);
        $reward->afterSave();
        $history = $this->historyFactory->create();
        $this->historyResource->load($history, $reward->getId(), 'reward_id');
        $this->assertNotNull($history->getId());
    }

    /**
     * @magentoDataFixture Magento/Reward/_files/reward.php
     *
     * @return void
     */
    public function testPointsLimit(): void
    {
        $this->prepareConfig('magento_reward/general/max_points_balance', 1000);
        $reward = $this->loadRewardDataByCustomerEmail('customer@example.com');
        $reward->setPointsDelta(1500);
        $reward->beforeSave();
        $this->assertEquals(1000, $reward->getPointsBalance());
    }

    /**
     * Prepare reward model
     *
     * @param string $email
     * @param int $attributeValue
     * @param int $pointsDelta
     * @return Reward
     */
    private function prepareRewardToMessageSend(string $email, int $attributeValue, int $pointsDelta): Reward
    {
        $customer = $this->customerRepository->get($email);
        $customer->setCustomAttribute('reward_update_notification', $attributeValue);
        $rewardModel = $this->rewardFactory->create();
        $rewardModel->setCustomer($customer);
        $rewardModel->setPointsDelta($pointsDelta);
        $rewardModel->setPointsBalance($pointsDelta);

        return $rewardModel;
    }

    /**
     * Load reward data by customer email
     *
     * @param string $customerEmail
     * @return Reward
     */
    private function loadRewardDataByCustomerEmail(string $customerEmail): Reward
    {
        $customer = $this->customerRepository->get($customerEmail);
        $reward = $this->rewardFactory->create();
        $reward->setCustomerId($customer->getId());
        $reward->setWebsiteId($customer->getWebsiteId());

        return $reward->loadByCustomer();
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
