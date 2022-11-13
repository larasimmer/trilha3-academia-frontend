<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Reward\Block\Customer;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\ObjectManagerInterface;
use Magento\Reward\Model\ResourceModel\Reward\History as HistoryResource;
use Magento\Reward\Model\RewardFactory;
use Magento\TestFramework\Helper\Bootstrap;

class ExpiredRewardsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /** @var HistoryResource */
    private $historyResource;

    /** @var RewardFactory */
    private $rewardFactory;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->historyResource = $this->objectManager->get(HistoryResource::class);
        $this->rewardFactory = $this->objectManager->get(RewardFactory::class);
        $this->resourceConnection = $this->objectManager->get(ResourceConnection::class);
    }

    /**
     * @magentoAppArea frontend
     * @magentoConfigFixture current_store magento_reward/general/expiration_days 0
     * @magentoConfigFixture current_store magento_reward/general/expiry_calculation dynamic
     * @magentoConfigFixture current_store magento_reward/points/order 1
     * @magentoDataFixture Magento/Reward/_files/customer_with_five_reward_points.php
     */
    public function testToHistoryHtml()
    {
        $customer = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
            \Magento\Customer\Model\Customer::class
        );
        $customer->load(1);

        \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(
            \Magento\Customer\Model\Session::class
        )->setCustomer(
            $customer
        );
        $utility = new \Magento\Framework\View\Utility\Layout($this);
        $layout = $utility->getLayoutFromFixture(
            __DIR__ . '/../../_files/magento_reward_customer_info.xml',
            $utility->getLayoutDependencies()
        );
        $layout->getUpdate()->addHandle('magento_reward_customer_info')->load();
        $layout->generateXml()->generateElements();
        $layout->addOutputElement('customer.reward');
        $reward = $this->rewardFactory->create();
        $reward->setCustomerId($customer->getId());
        $reward->setWebsiteId($customer->getWebsiteId());
        $reward->loadByCustomer();
        $this->resourceConnection->getConnection()
            ->update(
                $this->resourceConnection->getTableName('magento_reward_history'),
                ['expired_at_dynamic' => date('Y-m-d H:i:s', strtotime('-1 day'))],
                $this->resourceConnection->getConnection()->quoteInto('reward_id = ?', $reward->getId())
            );
        $this->historyResource->expirePoints($reward->getWebsiteId(), 'dynamic', 100);
        $format = '%AExpired reward%A';
        $this->assertStringMatchesFormat($format, $layout->getOutput());
    }
}
