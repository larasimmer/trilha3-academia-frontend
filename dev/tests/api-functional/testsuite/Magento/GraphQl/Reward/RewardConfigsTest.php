<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Reward;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Api\StoreResolverInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Get reward point configurations
 */
class RewardConfigsTest extends GraphQlAbstract
{

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var array
     */
    private $response;

    /**
     * @var string
     */
    private $storeId;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->scopeConfig = $objectManager->get(ScopeConfigInterface::class);
        /* @var StoreResolverInterface $storeResolver */
        $storeResolver = $objectManager->get(StoreResolverInterface::class);
        $this->storeId = $storeResolver->getCurrentStoreId();
    }

    /**
     * Test defined reward point configurations
     *
     * @magentoConfigFixture magento_reward/general/is_enabled 1
     * @magentoConfigFixture magento_reward/general/is_enabled_on_front 1
     * @magentoConfigFixture magento_reward/general/publish_history 1
     * @magentoConfigFixture magento_reward/general/min_points_balance 10
     * @magentoConfigFixture magento_reward/points/order 10
     * @magentoConfigFixture magento_reward/points/register Register
     * @magentoConfigFixture magento_reward/points/newsletter Newsletter
     * @magentoConfigFixture magento_reward/points/invitation_customer Invitation Customer
     * @magentoConfigFixture magento_reward/points/invitation_customer_limit 5
     * @magentoConfigFixture magento_reward/points/invitation_order 1
     * @magentoConfigFixture magento_reward/points/invitation_order_limit 10
     * @magentoConfigFixture magento_reward/points/review 15
     * @magentoConfigFixture magento_reward/points/review_limit 20
     */
    public function testDefinedRewardPointConfigs(): void
    {
        $this->queryRewardPointConfigs();
    }

    /**
     * Test default reward point configurations
     */
    public function testDefaultRewardPointConfigs(): void
    {
        $this->queryRewardPointConfigs();
    }

    /**
     * Fetch reward point configurations
     */
    private function queryRewardPointConfigs(): void
    {
        $query
            = <<<QUERY
{
  storeConfig {
    magento_reward_general_is_enabled
    magento_reward_general_is_enabled_on_front
    magento_reward_general_publish_history
    magento_reward_general_min_points_balance
    magento_reward_points_order
    magento_reward_points_register
    magento_reward_points_newsletter
    magento_reward_points_invitation_customer
    magento_reward_points_invitation_customer_limit
    magento_reward_points_invitation_order
    magento_reward_points_invitation_order_limit
    magento_reward_points_review
    magento_reward_points_review_limit
  }
}

QUERY;
        $this->response = $this->graphQlQuery($query);
        $this->assertArrayHasKey('storeConfig', $this->response);

        $this->compareConfig('magento_reward/general/is_enabled', 'magento_reward_general_is_enabled');
        $this->compareConfig(
            'magento_reward/general/is_enabled_on_front',
            'magento_reward_general_is_enabled_on_front'
        );
        $this->compareConfig('magento_reward/general/publish_history', 'magento_reward_general_publish_history');
        $this->compareConfig('magento_reward/general/min_points_balance', 'magento_reward_general_min_points_balance');
        $this->compareConfig('magento_reward/points/order', 'magento_reward_points_order');
        $this->compareConfig('magento_reward/points/register', 'magento_reward_points_register');
        $this->compareConfig('magento_reward/points/newsletter', 'magento_reward_points_newsletter');
        $this->compareConfig('magento_reward/points/invitation_customer', 'magento_reward_points_invitation_customer');
        $this->compareConfig(
            'magento_reward/points/invitation_customer_limit',
            'magento_reward_points_invitation_customer_limit'
        );
        $this->compareConfig('magento_reward/points/invitation_order', 'magento_reward_points_invitation_order');
        $this->compareConfig(
            'magento_reward/points/invitation_order_limit',
            'magento_reward_points_invitation_order_limit'
        );
        $this->compareConfig('magento_reward/points/review', 'magento_reward_points_review');
        $this->compareConfig('magento_reward/points/review_limit', 'magento_reward_points_review_limit');
    }

    /**
     * Compares configurations returned by reward point query
     *
     * @param string $configPath
     * @param string $key
     */
    private function compareConfig($configPath, $key): void
    {
        $this->assertArrayHasKey($key, $this->response['storeConfig']);
        $this->assertEquals($this->scopeConfig->getValue(
            $configPath,
            ScopeInterface::SCOPE_STORE,
            $this->storeId
        ), $this->response['storeConfig'][$key]);
    }
}
