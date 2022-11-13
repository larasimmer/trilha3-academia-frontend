<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Reward;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\GraphQl\GetCustomerAuthenticationHeader;
use Magento\GraphQl\Quote\GetMaskedQuoteIdByReservedOrderId;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResource;
use Magento\Reward\Api\RewardManagementInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Get applied reward points from cart
 */
class RetrieveAppliedRewardPointsFromCartTest extends GraphQlAbstract
{
    /**
     * @var CustomerTokenServiceInterface
     */
    private $customerTokenService;

    /**
     * @var GetMaskedQuoteIdByReservedOrderId
     */
    private $getMaskedQuoteIdByReservedOrderId;

    /**
     * @var RewardManagementInterface
     */
    private $rewardManagement;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var GetCustomerAuthenticationHeader
     */
    private $customerAuthenticationHeader;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->customerTokenService = $this->objectManager->get(CustomerTokenServiceInterface::class);
        $this->getMaskedQuoteIdByReservedOrderId = $this->objectManager->get(GetMaskedQuoteIdByReservedOrderId::class);
        $this->rewardManagement = $this->objectManager->get(RewardManagementInterface::class);
        $this->productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $this->customerAuthenticationHeader = $this->objectManager->get(GetCustomerAuthenticationHeader::class);
    }

    /**
     * Test applied reward points in cart
     *
     * @magentoApiDataFixture Magento/Reward/_files/rate.php
     * @magentoApiDataFixture Magento/Reward/_files/reward_points.php
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/customer/create_empty_cart.php
     */
    public function testRetrieveAppliedRewardPointsFromCart(): void
    {
        $quantity = 1;
        $sku = 'simple_product';

        /* @var CartInterface $quote */
        $quote = $this->objectManager->create(CartInterface::class);

        /* @var $quoteResource QuoteResource */
        $quoteResource = $this->objectManager->create(QuoteResource::class);
        $quoteResource->load($quote, 'test_quote', 'reserved_order_id');

        $product = $this->productRepository->get($sku);
        $quote->addProduct($product, $quantity);
        $quote->collectTotals()->setTotalsCollectedFlag(false);
        $quoteResource->save($quote);

        $cartId = $quote->getId();
        $this->rewardManagement->set($cartId);

        $response = $this->executeQuery();
        $assertionMap = [
            'cart' => [
                'applied_reward_points' => [
                    'money' => [
                        'currency' => 'USD',
                        'value' => 2
                    ],
                    'points' => 200
                ]
            ]
        ];

        $this->assertResponseFields($response, $assertionMap);
    }

    /**
     * Test not applied reward points in cart
     *
     * @magentoApiDataFixture Magento/Reward/_files/rate.php
     * @magentoApiDataFixture Magento/Reward/_files/reward_points.php
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/customer/create_empty_cart.php
     */
    public function testRetrieveNotAppliedRewardPointsFromCart(): void
    {
        $quantity = 1;
        $sku = 'simple_product';

        /* @var CartInterface $quote */
        $quote = $this->objectManager->create(CartInterface::class);

        /* @var $quoteResource QuoteResource */
        $quoteResource = $this->objectManager->create(QuoteResource::class);
        $quoteResource->load($quote, 'test_quote', 'reserved_order_id');

        $product = $this->productRepository->get($sku);
        $quote->addProduct($product, $quantity);

        $response = $this->executeQuery();
        $this->assertArrayHasKey('cart', $response);
        $this->assertArrayHasKey('applied_reward_points', $response['cart']);
        $this->assertEquals(null, $response['cart']['applied_reward_points']);
    }

    /**
     * Fetch applied reward points to cart
     *
     * @return array|bool|float|int|string
     */
    private function executeQuery()
    {
        $maskedCartId = $this->getMaskedQuoteIdByReservedOrderId->execute('test_quote');
        $query = <<<QUERY
{
  cart(cart_id: "{$maskedCartId}") {
    applied_reward_points {
      money {
        currency
        value
      }
      points
    }
  }
}
QUERY;
        return $this->graphQlQuery(
            $query,
            [],
            '',
            $this->customerAuthenticationHeader->execute('customer@example.com', 'password')
        );
    }
}
