<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Reward\Customer;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\GraphQl\GetCustomerAuthenticationHeader;
use Magento\Reward\Model\Reward;
use Magento\Reward\Model\RewardFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test reward points to Customer type
 */
class RewardPointsTest extends GraphQlAbstract
{
    /**
     * @var \Magento\TestFramework\ObjectManager
     */
    private $objectManager;

    /**
     * @var GetCustomerAuthenticationHeader
     */
    private $getCustomerAuthenticationHeader;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->getCustomerAuthenticationHeader = $this->objectManager->get(GetCustomerAuthenticationHeader::class);
    }

    /**
     * Test unauthorized customer
     */
    public function testUnauthorizedCustomer()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The current customer isn\'t authorized.');

        $query = <<<QUERY
{
  customer {
    reward_points {
      balance {
        points
      }
    }
  }
}
QUERY;
        $this->graphQlQuery($query);
    }

    /**
     * Test feature availability scenarios
     *
     * @magentoConfigFixture magento_reward/general/is_enabled 0
     * @magentoApiDataFixture Magento/Reward/_files/reward_points.php
     */
    public function testRewardPointsDisabled()
    {
        $query = <<<QUERY
{
  customer {
    reward_points {
      balance {
        points
      }
    }
  }
}
QUERY;
        $response = $this->graphQlQuery(
            $query,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute('customer@example.com', 'password')
        );

        $this->assertNull($response['customer']['reward_points']);
    }

    /**
     * Test feature availability scenarios
     *
     * @magentoConfigFixture magento_reward/general/is_enabled 1
     * @magentoConfigFixture magento_reward/general/is_enabled_on_front 0
     * @magentoApiDataFixture Magento/Reward/_files/reward_points.php
     */
    public function testRewardPointsDisabledOnFront()
    {
        $query = <<<QUERY
{
  customer {
    reward_points {
      balance {
        points
      }
    }
  }
}
QUERY;
        $response = $this->graphQlQuery(
            $query,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute('customer@example.com', 'password')
        );

        $this->assertNull($response['customer']['reward_points']);
    }

    /**
     * Test the balance
     *
     * @magentoConfigFixture magento_reward/general/is_enabled 1
     * @magentoConfigFixture magento_reward/general/is_enabled_on_front 1
     * @magentoApiDataFixture Magento/Reward/_files/reward_points.php
     * @magentoApiDataFixture Magento/Reward/_files/multiple_rates.php
     */
    public function testBalance()
    {
        /** @var CustomerRepositoryInterface $customerRepository */
        $customerRepository = $this->objectManager->get(CustomerRepositoryInterface::class);
        /** @var CustomerInterface $customer */
        $customer = $customerRepository->get('customer@example.com');
        $rewardFactory = $this->objectManager->get(RewardFactory::class);
        /** @var Reward $rewardInstance */
        $rewardInstance = $rewardFactory->create()->setCustomer($customer)->setWebsiteId(1)->loadByCustomer();

        $query = <<<QUERY
{
  customer {
    reward_points {
      balance {
        money {
          currency
          value
        }
        points
      }
    }
  }
}
QUERY;
        $response = $this->graphQlQuery(
            $query,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute('customer@example.com', 'password')
        );

        $this->assertNotEmpty($response['customer']['reward_points']['balance']);
        $this->assertNotEmpty($response['customer']['reward_points']['balance']['money']);
        $this->assertEquals(
            $rewardInstance->getCurrencyAmount(),
            (float)$response['customer']['reward_points']['balance']['money']['value']
        );
        $this->assertEquals(
            (int)$rewardInstance->getPointsBalance(),
            $response['customer']['reward_points']['balance']['points']
        );
    }

    /**
     * Test the exchange rates
     *
     * @magentoConfigFixture magento_reward/general/is_enabled 1
     * @magentoConfigFixture magento_reward/general/is_enabled_on_front 1
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/Reward/_files/multiple_rates.php
     */
    public function testExchangeRates()
    {
        $query = <<<QUERY
{
  customer {
    reward_points {
      exchange_rates {
        earning {
          points
          currency_amount
        }
        redemption {
          points
          currency_amount
        }
      }
    }
  }
}
QUERY;
        $response = $this->graphQlQuery(
            $query,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute('customer@example.com', 'password')
        );

        $this->assertNotEmpty($response['customer']['reward_points']['exchange_rates']);
        /** @var array $exchangeRates */
        $exchangeRates = $response['customer']['reward_points']['exchange_rates'];
        $this->assertEquals(1, $exchangeRates['earning']['points']);
        $this->assertEquals(5, $exchangeRates['earning']['currency_amount']);
        $this->assertEquals(7, $exchangeRates['redemption']['points']);
        $this->assertEquals(1, $exchangeRates['redemption']['currency_amount']);
    }

    /**
     * Test missing exchange rates
     * The data fixture coming from rate.php will only set redemption rate
     * We need to assert when there is no available data for earning
     *
     * @magentoConfigFixture magento_reward/general/is_enabled 1
     * @magentoConfigFixture magento_reward/general/is_enabled_on_front 1
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/Reward/_files/rate.php
     */
    public function testMissingExchangeRates()
    {
        $query = <<<QUERY
{
  customer {
    reward_points {
      exchange_rates {
        earning {
          points
          currency_amount
        }
      }
    }
  }
}
QUERY;
        $response = $this->graphQlQuery(
            $query,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute('customer@example.com', 'password')
        );

        $exchangeRates = $response['customer']['reward_points']['exchange_rates'];
        $this->assertEquals(0, $exchangeRates['earning']['points']);
        $this->assertEquals(0, $exchangeRates['earning']['currency_amount']);
    }

    /**
     * Test the updates subscription status
     *
     * @magentoConfigFixture magento_reward/general/is_enabled 1
     * @magentoConfigFixture magento_reward/general/is_enabled_on_front 1
     * @magentoApiDataFixture Magento/Reward/_files/customer_subscribed_for_update_notifications.php
     */
    public function testUpdatesSubscriptionStatus()
    {
        $query = <<<QUERY
{
  customer {
    reward_points {
      subscription_status {
        balance_updates
      }
    }
  }
}
QUERY;
        $response = $this->graphQlQuery(
            $query,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute('customer@example.com', 'password')
        );

        $this->assertNotEmpty($response['customer']['reward_points']['subscription_status']);
        $this->assertNotEmpty($response['customer']['reward_points']['subscription_status']['balance_updates']);
        $this->assertEquals(
            'SUBSCRIBED',
            $response['customer']['reward_points']['subscription_status']['balance_updates']
        );
    }

    /**
     * Test the warnings subscription status
     *
     * @magentoConfigFixture magento_reward/general/is_enabled 1
     * @magentoConfigFixture magento_reward/general/is_enabled_on_front 1
     * @magentoApiDataFixture Magento/Reward/_files/customer_subscribed_for_warning_notifications.php
     */
    public function testWarningsSubscriptionStatus()
    {
        $query = <<<QUERY
{
  customer {
    reward_points {
      subscription_status {
        points_expiration_notifications
      }
    }
  }
}
QUERY;
        $response = $this->graphQlQuery(
            $query,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute('customer@example.com', 'password')
        );

        $subscriptionStatus = $response['customer']['reward_points']['subscription_status'];
        $this->assertNotEmpty($subscriptionStatus);
        $this->assertNotEmpty($subscriptionStatus['points_expiration_notifications']);
        $this->assertEquals(
            'SUBSCRIBED',
            $subscriptionStatus['points_expiration_notifications']
        );
    }

    /**
     * Test the balance history
     *
     * @magentoConfigFixture magento_reward/general/is_enabled 1
     * @magentoConfigFixture magento_reward/general/is_enabled_on_front 1
     * @magentoConfigFixture magento_reward/general/publish_history 1
     * @magentoApiDataFixture Magento/Reward/_files/reward_points.php
     */
    public function testBalanceHistory()
    {
        $query = <<<QUERY
{
  customer {
    reward_points {
      balance_history {
        balance {
          money {
            currency
            value
          }
          points
        }
        change_reason
        date
        points_change
      }
    }
  }
}
QUERY;
        $response = $this->graphQlQuery(
            $query,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute('customer@example.com', 'password')
        );

        $balanceHistory = $response['customer']['reward_points']['balance_history'];
        $this->assertNotEmpty($balanceHistory);
        $this->assertNotEmpty($balanceHistory[0]['balance']['money']);
        $this->assertNotEmpty($balanceHistory[0]['balance']['money']['currency']);
        $this->assertNotEmpty($balanceHistory[0]['balance']['points']);
        $this->assertEquals(200, $balanceHistory[0]['balance']['points']);
        $this->assertEquals(200, $balanceHistory[0]['points_change']);
        $this->assertEquals('Updated by moderator', $balanceHistory[0]['change_reason']);
    }

    /**
     * Test the availability of the balance history
     *
     * @magentoConfigFixture magento_reward/general/is_enabled 1
     * @magentoConfigFixture magento_reward/general/is_enabled_on_front 1
     * @magentoConfigFixture magento_reward/general/publish_history 0
     * @magentoApiDataFixture Magento/Reward/_files/reward_points.php
     */
    public function testBalanceHistoryDisabled()
    {
        $query = <<<QUERY
{
  customer {
    reward_points {
      balance_history {
        points_change
      }
    }
  }
}
QUERY;
        $response = $this->graphQlQuery(
            $query,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute('customer@example.com', 'password')
        );

        $balanceHistory = $response['customer']['reward_points']['balance_history'];
        $this->assertEmpty($balanceHistory);
    }
}
