<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\GiftRegistry;

use Magento\Catalog\Api\ProductCustomOptionRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\GraphQl\Quote\GetMaskedQuoteIdByReservedOrderId;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Quote\Model\GetQuoteByReservedOrderId;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test moving cart items to the gift registry
 */
class MoveCartItemsToGiftRegistryTest extends GraphQlAbstract
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
     * @var GetQuoteByReservedOrderId
     */
    private $getQuoteByReservedOrderId;

    /**
     * @var ProductCustomOptionRepositoryInterface
     */
    private $productCustomOptionsRepository;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var Uid
     */
    private $idEncoder;

    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * @var StockItemRepositoryInterface
     */
    private $stockItemRepository;

    /**
     * Set Up
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->customerTokenService = $objectManager->get(CustomerTokenServiceInterface::class);
        $this->idEncoder = $objectManager->create(Uid::class);
        $this->getMaskedQuoteIdByReservedOrderId = $objectManager->get(GetMaskedQuoteIdByReservedOrderId::class);
        $this->getQuoteByReservedOrderId = $objectManager->get(GetQuoteByReservedOrderId::class);
        $this->productCustomOptionsRepository = $objectManager->get(ProductCustomOptionRepositoryInterface::class);
        $this->productRepository = $objectManager->get(ProductRepositoryInterface::class);
        $this->productRepository->cleanCache();
        $this->stockRegistry = Bootstrap::getObjectManager()->get(StockRegistryInterface::class);
        $this->stockItemRepository = Bootstrap::getObjectManager()->get(StockItemRepositoryInterface::class);
    }

    /**
     * Move simple product from cart to the gift registry
     *
     * @magentoApiDataFixture Magento/GiftRegistry/_files/gift_registry_entity_birthday_type.php
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/customer/create_empty_cart.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     *
     * @throws AuthenticationException
     */
    public function testMovingSimpleProductToGiftRegistry(): void
    {
        $authHeaders = $this->getCustomerAuthHeaders('customer@example.com', 'password');
        $giftRegistry = $this->getGiftRegistry();

        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('test_quote');
        $quoteItems = $this->getQuoteByReservedOrderId->execute('test_quote')->getAllVisibleItems();

        $response = $this->graphQlMutation(
            $this->getMoveCartItemsToGiftRegistryMutation($giftRegistry['uid'], $maskedQuoteId),
            [],
            '',
            $authHeaders
        );

        $this->assertArrayHasKey('moveCartItemsToGiftRegistry', $response);
        $this->assertEquals(true, $response['moveCartItemsToGiftRegistry']['status']);
        $this->assertEmpty($response['moveCartItemsToGiftRegistry']['user_errors']);

        $items = $response['moveCartItemsToGiftRegistry']['gift_registry']['items'];
        $this->assertEquals($quoteItems[0]->getQty(), $items[0]['quantity']);
        $this->assertEquals($quoteItems[0]->getSku(), $items[0]['product']['sku']);
    }

    /**
     * Move virtual product from cart to the gift registry
     *
     * @magentoApiDataFixture Magento/GiftRegistry/_files/gift_registry_entity_birthday_type.php
     * @magentoApiDataFixture Magento/Catalog/_files/product_virtual.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/customer/create_empty_cart.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/add_virtual_product.php
     *
     * @throws AuthenticationException
     */
    public function testMovingVirtualProductToGiftRegistry(): void
    {
        $authHeaders = $this->getCustomerAuthHeaders('customer@example.com', 'password');
        $giftRegistry = $this->getGiftRegistry();

        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('test_quote');
        $quoteItems = $this->getQuoteByReservedOrderId->execute('test_quote')->getAllVisibleItems();

        $response = $this->graphQlMutation(
            $this->getMoveCartItemsToGiftRegistryMutation($giftRegistry['uid'], $maskedQuoteId),
            [],
            '',
            $authHeaders
        );

        $this->assertArrayHasKey('moveCartItemsToGiftRegistry', $response);
        $this->assertEquals([], $response['moveCartItemsToGiftRegistry']['gift_registry']['items']);
        $this->assertEquals(false, $response['moveCartItemsToGiftRegistry']['status']);

        $userErrors = $response['moveCartItemsToGiftRegistry']['user_errors'];
        $this->assertNotEmpty($userErrors);
        $this->assertEquals('UNDEFINED', $userErrors[0]['code']);
        $this->assertEquals($giftRegistry['uid'], $userErrors[0]['gift_registry_uid']);
        $this->assertEquals($this->idEncoder->encode($quoteItems[0]->getProductId()), $userErrors[0]['product_uid']);
        $this->assertEquals(
            'You can\'t add virtual products, digital products or gift cards to gift registries.',
            $userErrors[0]['message']
        );
    }

    /**
     * Move bundle product from cart to the gift registry
     *
     * @magentoApiDataFixture Magento/GiftRegistry/_files/gift_registry_entity_birthday_type.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/customer/create_empty_cart.php
     * @magentoApiDataFixture Magento/GiftRegistry/_files/quote/add_bundle_product.php
     */
    public function testMovingBundleProductToGiftRegistry(): void
    {
        $authHeaders = $this->getCustomerAuthHeaders('customer@example.com', 'password');
        $giftRegistry = $this->getGiftRegistry();

        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('test_quote');
        $quoteItem = $this->getQuoteByReservedOrderId->execute('test_quote')->getAllVisibleItems()[0];

        $response = $this->graphQlMutation(
            $this->getMoveCartItemsToGiftRegistryMutation($giftRegistry['uid'], $maskedQuoteId),
            [],
            '',
            $authHeaders
        );

        $this->assertArrayHasKey('moveCartItemsToGiftRegistry', $response);
        $this->assertEquals(true, $response['moveCartItemsToGiftRegistry']['status']);
        $this->assertEmpty($response['moveCartItemsToGiftRegistry']['user_errors']);

        $items = $response['moveCartItemsToGiftRegistry']['gift_registry']['items'];
        $this->assertEquals($quoteItem->getQty(), $items[0]['quantity']);
        $this->assertEquals($quoteItem->getSku(), $items[0]['product']['sku']);
    }

    /**
     * Move configurable product from cart to the gift registry
     *
     * @magentoApiDataFixture Magento/GiftRegistry/_files/gift_registry_entity_birthday_type.php
     * @magentoApiDataFixture Magento/CatalogRule/_files/configurable_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/customer/create_empty_cart.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/add_configurable_product.php
     */
    public function testMovingConfigurableProductToGiftRegistry(): void
    {
        $authHeaders = $this->getCustomerAuthHeaders('customer@example.com', 'password');
        $giftRegistry = $this->getGiftRegistry();

        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('test_quote');
        $quoteItem = $this->getQuoteByReservedOrderId->execute('test_quote')->getAllVisibleItems()[0];

        $response = $this->graphQlMutation(
            $this->getMoveCartItemsToGiftRegistryMutation($giftRegistry['uid'], $maskedQuoteId),
            [],
            '',
            $authHeaders
        );

        $this->assertArrayHasKey('moveCartItemsToGiftRegistry', $response);
        $this->assertEquals(true, $response['moveCartItemsToGiftRegistry']['status']);
        $this->assertEmpty($response['moveCartItemsToGiftRegistry']['user_errors']);

        $items = $response['moveCartItemsToGiftRegistry']['gift_registry']['items'];
        $this->assertEquals($quoteItem->getQty(), $items[0]['quantity']);
        $this->assertEquals('configurable', $items[0]['product']['sku']);
    }

    /**
     * Test moving a simple product with empty values for required options
     *
     * @magentoConfigFixture cataloginventory/options/enable_inventory_check 1
     * @magentoApiDataFixture Magento/GiftRegistry/_files/gift_registry_entity_birthday_type.php
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/set_custom_options_simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/customer/create_empty_cart.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     */
    public function testMovingSimpleProductWithEmptyRequiredOptions()
    {
        $authHeaders = $this->getCustomerAuthHeaders('customer@example.com', 'password');
        $giftRegistry = $this->getGiftRegistry();

        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('test_quote');
        $quoteItem = $this->getQuoteByReservedOrderId->execute('test_quote')->getAllVisibleItems()[0];

        $this->setProductCustomOptionsAsReguired($quoteItem->getProduct());

        $response = $this->graphQlMutation(
            $this->getMoveCartItemsToGiftRegistryMutation($giftRegistry['uid'], $maskedQuoteId),
            [],
            '',
            $authHeaders
        );

        $this->assertArrayHasKey('moveCartItemsToGiftRegistry', $response);
        $this->assertEquals([], $response['moveCartItemsToGiftRegistry']['gift_registry']['items']);
        $this->assertEquals(false, $response['moveCartItemsToGiftRegistry']['status']);

        $userErrors = $response['moveCartItemsToGiftRegistry']['user_errors'];
        $this->assertNotEmpty($userErrors);
        $this->assertEquals('UNDEFINED', $userErrors[0]['code']);
        $this->assertEquals($giftRegistry['uid'], $userErrors[0]['gift_registry_uid']);
        $this->assertEquals($this->idEncoder->encode($quoteItem->getProductId()), $userErrors[0]['product_uid']);
        $this->assertEquals(
            'The product has required options. Enter the options and try again.',
            $userErrors[0]['message']
        );
    }

    /**
     * Test moving simple product with empty values for required options when item data check is disabled on quot load.
     *
     * @magentoConfigFixture cataloginventory/options/enable_inventory_check 0
     * @magentoApiDataFixture Magento/GiftRegistry/_files/gift_registry_entity_birthday_type.php
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/set_custom_options_simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/customer/create_empty_cart.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     */
    public function testMovingSimpleProductWithEmptyRequiredOptionsWithDisabledInventoryCheck()
    {
        $authHeaders = $this->getCustomerAuthHeaders('customer@example.com', 'password');
        $giftRegistry = $this->getGiftRegistry();

        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('test_quote');
        $quoteItem = $this->getQuoteByReservedOrderId->execute('test_quote')->getAllVisibleItems()[0];

        $this->setProductCustomOptionsAsReguired($quoteItem->getProduct());

        $response = $this->graphQlMutation(
            $this->getMoveCartItemsToGiftRegistryMutation($giftRegistry['uid'], $maskedQuoteId),
            [],
            '',
            $authHeaders
        );

        $this->assertArrayHasKey('moveCartItemsToGiftRegistry', $response);
        $this->assertEquals(true, $response['moveCartItemsToGiftRegistry']['status']);
        $this->assertEmpty($response['moveCartItemsToGiftRegistry']['user_errors']);

        $items = $response['moveCartItemsToGiftRegistry']['gift_registry']['items'];
        $this->assertEquals($quoteItem->getQty(), $items[0]['quantity']);
        $this->assertEquals($quoteItem->getSku(), $items[0]['product']['sku']);
    }

    /**
     * Move virtual and simple products from cart to the gift registry
     *
     * @magentoApiDataFixture Magento/GiftRegistry/_files/gift_registry_entity_birthday_type.php
     * @magentoApiDataFixture Magento/Catalog/_files/product_virtual.php
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/customer/create_empty_cart.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/add_virtual_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     *
     * @throws AuthenticationException
     */
    public function testMovingMixedCartToGiftRegistry(): void
    {
        $authHeaders = $this->getCustomerAuthHeaders('customer@example.com', 'password');
        $giftRegistry = $this->getGiftRegistry();

        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('test_quote');
        $quoteItems = $this->getQuoteByReservedOrderId->execute('test_quote')->getAllVisibleItems();

        $response = $this->graphQlMutation(
            $this->getMoveCartItemsToGiftRegistryMutation($giftRegistry['uid'], $maskedQuoteId),
            [],
            '',
            $authHeaders
        );

        $this->assertArrayHasKey('moveCartItemsToGiftRegistry', $response);
        $this->assertEquals(false, $response['moveCartItemsToGiftRegistry']['status']);

        $virtualItem = $quoteItems[0];
        $userErrors = $response['moveCartItemsToGiftRegistry']['user_errors'];
        $this->assertNotEmpty($userErrors);
        $this->assertEquals('UNDEFINED', $userErrors[0]['code']);
        $this->assertEquals($giftRegistry['uid'], $userErrors[0]['gift_registry_uid']);
        $this->assertEquals($this->idEncoder->encode($virtualItem->getProductId()), $userErrors[0]['product_uid']);
        $this->assertEquals(
            'You can\'t add virtual products, digital products or gift cards to gift registries.',
            $userErrors[0]['message']
        );

        $simpleItem = $quoteItems[1];
        $items = $response['moveCartItemsToGiftRegistry']['gift_registry']['items'];
        $this->assertEquals($simpleItem->getQty(), $items[0]['quantity']);
        $this->assertEquals($simpleItem->getSku(), $items[0]['product']['sku']);
    }

    /**
     * Test moving for empty cart
     *
     * @magentoApiDataFixture Magento/GiftRegistry/_files/gift_registry_entity_birthday_type.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/customer/create_empty_cart.php
     *
     * @throws AuthenticationException
     */
    public function testMovingForEmptyCart(): void
    {
        $authHeaders = $this->getCustomerAuthHeaders('customer@example.com', 'password');
        $giftRegistry = $this->getGiftRegistry();

        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('test_quote');

        $response = $this->graphQlMutation(
            $this->getMoveCartItemsToGiftRegistryMutation($giftRegistry['uid'], $maskedQuoteId),
            [],
            '',
            $authHeaders
        );

        $this->assertArrayHasKey('moveCartItemsToGiftRegistry', $response);
        $this->assertEquals([], $response['moveCartItemsToGiftRegistry']['gift_registry']['items']);
        $this->assertEquals([], $response['moveCartItemsToGiftRegistry']['user_errors']);
    }

    /**
     * Move out of stock product
     *
     * @magentoConfigFixture cataloginventory/options/enable_inventory_check 1
     * @magentoApiDataFixture Magento/GiftRegistry/_files/gift_registry_entity_birthday_type.php
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/customer/create_empty_cart.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     */
    public function testMovingWithOutOfStockProduct(): void
    {
        $authHeaders = $this->getCustomerAuthHeaders('customer@example.com', 'password');
        $giftRegistry = $this->getGiftRegistry();

        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('test_quote');
        $quoteItem = $this->getQuoteByReservedOrderId->execute('test_quote')->getAllVisibleItems()[0];

        $productId = $quoteItem->getProductId();

        $this->makeProductOutOfStock((int)$productId, (int)$quoteItem->getStore()->getWebsiteId());

        $response = $this->graphQlMutation(
            $this->getMoveCartItemsToGiftRegistryMutation($giftRegistry['uid'], $maskedQuoteId),
            [],
            '',
            $authHeaders
        );
        $this->assertArrayHasKey('moveCartItemsToGiftRegistry', $response);
        $this->assertEquals([], $response['moveCartItemsToGiftRegistry']['gift_registry']['items']);
        $this->assertEquals(false, $response['moveCartItemsToGiftRegistry']['status']);

        $userErrors = $response['moveCartItemsToGiftRegistry']['user_errors'];
        $this->assertNotEmpty($userErrors);
        $this->assertEquals('OUT_OF_STOCK', $userErrors[0]['code']);
        $this->assertEquals($giftRegistry['uid'], $userErrors[0]['gift_registry_uid']);
        $this->assertEquals($this->idEncoder->encode($productId), $userErrors[0]['product_uid']);
        $expectedErrorMessages = [
            'There are no source items with the in stock status',
            'This product is out of stock.'
        ];
        $this->assertContains(
            $userErrors[0]['message'],
            $expectedErrorMessages
        );
    }

    /**
     * Move out of stock product when item data check is disabled on quot load.
     *
     * @magentoConfigFixture cataloginventory/options/enable_inventory_check 0
     * @magentoApiDataFixture Magento/GiftRegistry/_files/gift_registry_entity_birthday_type.php
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/customer/create_empty_cart.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     */
    public function testMovingWithOutOfStockProductWithDisabledInventoryCheck(): void
    {
        $authHeaders = $this->getCustomerAuthHeaders('customer@example.com', 'password');
        $giftRegistry = $this->getGiftRegistry();

        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('test_quote');
        $quoteItem = $this->getQuoteByReservedOrderId->execute('test_quote')->getAllVisibleItems()[0];

        $productId = $quoteItem->getProductId();

        $this->makeProductOutOfStock((int)$productId, (int)$quoteItem->getStore()->getWebsiteId());

        $response = $this->graphQlMutation(
            $this->getMoveCartItemsToGiftRegistryMutation($giftRegistry['uid'], $maskedQuoteId),
            [],
            '',
            $authHeaders
        );

        $this->assertArrayHasKey('moveCartItemsToGiftRegistry', $response);
        $this->assertEquals(true, $response['moveCartItemsToGiftRegistry']['status']);
        $this->assertEmpty($response['moveCartItemsToGiftRegistry']['user_errors']);

        $items = $response['moveCartItemsToGiftRegistry']['gift_registry']['items'];
        $this->assertEquals($quoteItem->getQty(), $items[0]['quantity']);
        $this->assertEquals($quoteItem->getSku(), $items[0]['product']['sku']);
    }

    /**
     * Makes provided product as out of stock.
     *
     * @param int $productId
     * @return void
     */
    private function makeProductOutOfStock(int $productId, int $websiteId): void
    {
        $stockItem = $this->stockRegistry->getStockItem($productId, $websiteId);
        $stockItem->setIsInStock(false);
        $this->stockItemRepository->save($stockItem);
    }

    /**
     * Set the product custom options as required
     *
     * @param Product $product
     */
    private function setProductCustomOptionsAsReguired(Product $product): void
    {
        $customOptions = $this->productCustomOptionsRepository->getList($product->getSku());
        if ($customOptions) {
            $product->setTypeHasRequiredOptions(true)->setRequiredOptions(true);
            $this->productRepository->save($product);

            foreach ($customOptions as $customOption) {
                $customOption->setIsRequire(true);
                $this->productCustomOptionsRepository->save($customOption);
            }
        }
    }

    /**
     * Get gift registry
     *
     * @return array
     *
     * @throws AuthenticationException
     */
    private function getGiftRegistry(): array
    {
        $authHeaders = $this->getCustomerAuthHeaders('customer@example.com', 'password');
        $giftRegistries = $this->graphQlQuery($this->getQuery(), [], '', $authHeaders);
        $this->assertNotEmpty($giftRegistries['customer']['gift_registries']);

        return $giftRegistries['customer']['gift_registries'][0];
    }

    /**
     * Get mutation
     *
     * @param string $giftRegistryUid
     * @param string $maskedQuoteId
     * @return string
     */
    private function getMoveCartItemsToGiftRegistryMutation(string $giftRegistryUid, string $maskedQuoteId): string
    {
        return <<<MUTATION
mutation {
  moveCartItemsToGiftRegistry(cartUid: "$maskedQuoteId", giftRegistryUid: "$giftRegistryUid") {
  gift_registry {
      uid
      created_at
      owner_name
      status
      type {label}
      message
      items{
        quantity
        product{sku name }
      }
    }
    status
    user_errors {
      code
      message
      product_uid
      gift_registry_uid
    }
  }
}
MUTATION;
    }

    /**
     * Get customer auth headers
     *
     * @param string $email
     * @param string $password
     *
     * @return array
     *
     * @throws AuthenticationException
     */
    private function getCustomerAuthHeaders(string $email, string $password): array
    {
        $customerToken = $this->customerTokenService->createCustomerAccessToken($email, $password);
        return ['Authorization' => 'Bearer ' . $customerToken];
    }

    /**
     * Get gift registry query
     *
     * @return string
     */
    private function getQuery(): string
    {
        return <<<QUERY
    query {
      customer {
        gift_registries {
          uid
        }
      }
    }
    QUERY;
    }
}
