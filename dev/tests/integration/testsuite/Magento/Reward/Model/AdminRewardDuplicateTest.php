<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Reward\Model;

use Magento\Cms\Model\Block;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Reward\Model\ResourceModel\Reward\History as HistoryResource;
use Magento\TestFramework\TestCase\AbstractBackendController;
use Magento\Reward\Block\Customer\Reward\History;

/**
 * @magentoConfigFixture current_store magento_reward/general/expiration_days 0
 * @magentoConfigFixture current_store magento_reward/general/expiry_calculation dynamic
 * @magentoConfigFixture current_store magento_reward/points/order 1
 * @magentoDataFixture Magento/Reward/_files/customer_with_five_reward_points.php
 */
class AdminRewardDuplicateTest extends AbstractBackendController
{
    /** @var HistoryResource */
    private $historyResource;

    /** @var CustomerRepositoryInterface */
    private $customerRepository;

    /** @var RewardFactory */
    private $rewardFactory;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var Block
     */
    private $block;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->customerRepository = $this->_objectManager->get(CustomerRepositoryInterface::class);
        $this->customerSession = $this->_objectManager->get(CustomerSession::class);
        $this->historyResource = $this->_objectManager->get(HistoryResource::class);
        $this->customerRepository = $this->_objectManager->get(CustomerRepositoryInterface::class);
        $this->rewardFactory = $this->_objectManager->get(RewardFactory::class);
        $this->resourceConnection = $this->_objectManager->get(ResourceConnection::class);
        $this->block = $this->_objectManager->get(History::class);
    }

    /**
     * To check expired reward point are visible on storefront
     * @return void
     * @throws NoSuchEntityException If customer with the specified email does not exist.
     * @throws LocalizedException
     */
    public function testExpiredRewardPointsAreVisibleOnStorefront()
    {
        $email = 'customer@example.com';
        $customerData = $this->customerRepository->get($email);
        $this->customerSession->setCustomerDataAsLoggedIn($customerData);
        $reward = $this->loadRewardDataByCustomerEmail($email);
        $this->resourceConnection->getConnection()
            ->update(
                $this->resourceConnection->getTableName('magento_reward_history'),
                ['expired_at_dynamic' => date('Y-m-d H:i:s', strtotime('-1 day'))],
                $this->resourceConnection->getConnection()->quoteInto('reward_id = ?', $reward->getId())
            );
        $this->historyResource->expirePoints($reward->getWebsiteId(), 'dynamic', 100);
        $result = $this->block->getHistory();
        $this->assertEquals(2, $result->getSize());
    }

    /**
     * Load reward data by customer email
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
}
