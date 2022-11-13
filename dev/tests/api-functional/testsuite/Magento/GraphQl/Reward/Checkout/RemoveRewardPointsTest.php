<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Reward\Checkout;

use Magento\Framework\Exception\AuthenticationException;
use Magento\GraphQl\GetCustomerAuthenticationHeader;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteIdMask;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Reward\Model\RewardManagement;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test removal reward points from cart
 */
class RemoveRewardPointsTest extends GraphQlAbstract
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var GetCustomerAuthenticationHeader
     */
    private $getCustomerAuthenticationHeader;

    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;

    /**
     * @var RewardManagement
     */
    private $rewardManagement;

    /**
     * Setup
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->getCustomerAuthenticationHeader = $this->objectManager->get(GetCustomerAuthenticationHeader::class);
        $this->quoteFactory = $this->objectManager->get(QuoteFactory::class);
        $this->quoteIdMaskFactory = $this->objectManager->get(QuoteIdMaskFactory::class);
        $this->rewardManagement = $this->objectManager->get(RewardManagement::class);
    }

    /**
     * Tests unauthorized access
     */
    public function testUnauthorizedCustomer()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The current customer isn\'t authorized.');
        $cartId = "1";

        $mutation = <<<MUTATION
mutation {
  removeRewardPointsFromCart(
    cartId: {$cartId}
  ) {
    cart {
      id
    }
  }
}
MUTATION;

        $this->graphQlMutation($mutation);
    }

    /**
     * Tests for a disabled feature
     *
     * @magentoConfigFixture magento_reward/general/is_enabled 0
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     */
    public function testRewardPointsDisabled()
    {
        $this->expectFeatureDisabled();
    }

    /**
     * Tests for a storefront disabled feature
     *
     * @magentoConfigFixture magento_reward/general/is_enabled 1
     * @magentoConfigFixture magento_reward/general/is_enabled_on_front 0
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     */
    public function testRewardPointsDisabledOnFront()
    {
        $this->expectFeatureDisabled();
    }

    /**
     * Asserts null response
     *
     * @throws AuthenticationException
     */
    private function expectFeatureDisabled()
    {
        $quoteMaskedId = "1";

        $mutation = <<<MUTATION
mutation {
  removeRewardPointsFromCart(
    cartId: {$quoteMaskedId}
  ) {
    cart {
      id
    }
  }
}
MUTATION;
        $response = $this->graphQlMutation(
            $mutation,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute('customer@example.com', 'password')
        );

        $this->assertTrue(null === $response['removeRewardPointsFromCart']);
    }

    /**
     * Tests removal of reward points
     *
     * @magentoApiDataFixture Magento/Checkout/_files/quote_with_items_saved.php
     * @magentoApiDataFixture Magento/Reward/_files/multiple_rates.php
     * @magentoApiDataFixture Magento/Reward/_files/reward_points.php
     */
    public function testRemoveRewardPoints()
    {
        /** @var Quote $quote */
        $quote = $this->quoteFactory->create()->load('test_order_item_with_items', 'reserved_order_id');
        /** @var string $quoteMaskedId */
        $quoteMaskedId = $this->getQuoteMaskedId($quote);
        /** Apply available points */
        $this->rewardManagement->set($quote->getId());
        $reloadedQuote = $this->quoteFactory->create()->load($quote->getId());
        /** @var float $grandTotalBeforePointsRemoved */
        $grandTotalBeforePointsRemoved = $reloadedQuote->getGrandTotal();

        $mutation = <<<MUTATION
mutation {
  removeRewardPointsFromCart(
    cartId: "{$quoteMaskedId}"
  ) {
    cart {
      prices{
        grand_total {
          value
        }
      }
    }
  }
}
MUTATION;
        $response = $this->graphQlMutation(
            $mutation,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute('customer@example.com', 'password')
        );

        /** @var float $grandTotalAfterPointsRemoved */
        $grandTotalAfterPointsRemoved
            = $response['removeRewardPointsFromCart']['cart']['prices']['grand_total']['value'];
        $this->assertEquals(2, $grandTotalBeforePointsRemoved);
        $this->assertEquals(30, $grandTotalAfterPointsRemoved);
    }

    /**
     * @param Quote $quote
     * @return string
     * @throws \Exception
     */
    private function getQuoteMaskedId($quote)
    {
        /** @var QuoteIdMask $quoteIdMask */
        $quoteIdMask = $this->quoteIdMaskFactory->create();
        $quoteIdMask->setQuoteId($quote->getId());
        $quoteIdMask->setDataChanges(true);
        $quoteIdMask->save();
        return $quoteIdMask->getMaskedId();
    }
}
