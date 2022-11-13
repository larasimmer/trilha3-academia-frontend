<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\CatalogPermissions;

use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\GraphQl\GetCustomerAuthenticationHeader;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;

/**
 * Test products that are not allowed to be added to cart
 */
class AddProductToCartTest extends GraphQlAbstract
{
    /**
     * @var \Magento\TestFramework\ObjectManager
     */
    private $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 1
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/CatalogPermissions/_files/category_products_deny_add_to_cart.php
     */
    public function testProductIsAddedToCart()
    {
        $this->reindexCatalogPermissions();

        $productSku = 'simple_allow_122';
        $desiredQuantity = 5;
        $currentEmail = 'customer@example.com';
        $currentPassword = 'password';
        $headerAuthorization = $this->objectManager->get(GetCustomerAuthenticationHeader::class)
            ->execute($currentEmail, $currentPassword);

        $cartId = $this->createEmptyCart($headerAuthorization);

        $query = <<<MUTATION
mutation {
  addProductsToCart(
    cartId: "{$cartId}",
    cartItems: [
      {
          sku: "{$productSku}"
          quantity: {$desiredQuantity}
      }

      ]
  ) {
    cart {
      items {
       quantity
       product {
          sku
        }
      }
    }
  }
}
MUTATION;

        $response = $this->graphQlMutation(
            $query,
            [],
            '',
            $headerAuthorization
        );

        $this->removeQuote($cartId);

        $this->assertNotEmpty($response['addProductsToCart']['cart']['items']);
        $cartItems = $response['addProductsToCart']['cart']['items'];
        $this->assertEquals($desiredQuantity, $cartItems[0]['quantity']);
        $this->assertEquals($productSku, $cartItems[0]['product']['sku']);
    }

    /**
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_catalog_category_view 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_catalog_product_price 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_checkout_items 1
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/CatalogPermissions/_files/category_products_deny_add_to_cart.php
     */
    public function testProductIsDeniedToCartForCustomer()
    {
        $this->reindexCatalogPermissions();

        $productSku = 'simple_deny_122';
        $desiredQuantity = 5;
        $currentEmail = 'customer@example.com';
        $currentPassword = 'password';
        $headerAuthorization = $this->objectManager->get(GetCustomerAuthenticationHeader::class)
            ->execute($currentEmail, $currentPassword);

        $cartId = $this->createEmptyCart($headerAuthorization);

        $mutation = <<<MUTATION
mutation {
  addProductsToCart(
    cartId: "{$cartId}",
    cartItems: [
      {
          sku: "{$productSku}"
          quantity: {$desiredQuantity}
      }

      ]
  ) {
    cart {
      items {
       quantity
       product {
          sku
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
            $headerAuthorization
        );

        $this->removeQuote($cartId);

        $this->assertEmpty($response['addProductsToCart']['cart']['items']);
    }

    /**
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_catalog_category_view 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_catalog_product_price 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_checkout_items 1
     * @magentoApiDataFixture Magento/CatalogPermissions/_files/category_products_deny_add_to_cart.php
     */
    public function testProductIsDeniedToCartForGuest()
    {
        $this->reindexCatalogPermissions();

        $productSku = 'simple_deny_122';
        $desiredQuantity = 5;

        $cartId = $this->createEmptyCart();

        $mutation = <<<MUTATION
mutation {
  addProductsToCart(
    cartId: "{$cartId}",
    cartItems: [
      {
          sku: "{$productSku}"
          quantity: {$desiredQuantity}
      }

      ]
  ) {
    cart {
      items {
       quantity
       product {
          sku
        }
      }
    }
  }
}
MUTATION;

        $response = $this->graphQlMutation($mutation);

        $this->removeQuote($cartId);

        $this->assertEmpty($response['addProductsToCart']['cart']['items']);
    }

    /**
     * @magentoConfigFixture catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_catalog_category_view 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_catalog_product_price 1
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_checkout_items 2
     * @magentoConfigFixture catalog/magento_catalogpermissions/grant_checkout_items_groups 1
     * @magentoApiDataFixture Magento/CatalogPermissions/_files/category_products.php
     */
    public function testProductIsDeniedToCartForGuestByConfiguration()
    {
        $this->reindexCatalogPermissions();

        $productSku = 'simple333';
        $desiredQuantity = 5;

        $cartId = $this->createEmptyCart();

        $mutation = <<<MUTATION
mutation {
  addProductsToCart(
    cartId: "{$cartId}",
    cartItems: [
      {
          sku: "{$productSku}"
          quantity: {$desiredQuantity}
      }

      ]
  ) {
    cart {
      items {
       quantity
       product {
          sku
        }
      }
    }
  }
}
MUTATION;

        $response = $this->graphQlMutation($mutation);

        $this->removeQuote($cartId);

        $this->assertEmpty($response['addProductsToCart']['cart']['items']);
    }

    /**
     * Reindex catalog permissions
     */
    private function reindexCatalogPermissions()
    {
        $appDir = dirname(Bootstrap::getInstance()->getAppTempDir());
        $out = '';
        // phpcs:ignore Magento2.Security.InsecureFunction
        exec("php -f {$appDir}/bin/magento indexer:reindex catalogpermissions_category", $out);
    }

    /**
     * Create empty cart
     *
     * @param array|null $headerAuthorization
     * @return string
     * @throws \Exception
     */
    private function createEmptyCart(array $headerAuthorization = null): string
    {
        $query = <<<QUERY
mutation {
  createEmptyCart
}
QUERY;
        $response = $this->graphQlMutation(
            $query,
            [],
            '',
            $headerAuthorization ?? []
        );
        $cartId = $response['createEmptyCart'];
        return $cartId;
    }

    /**
     * Remove the quote
     *
     * @param string $maskedId
     */
    private function removeQuote(string $maskedId): void
    {
        $maskedIdToQuote = $this->objectManager->get(MaskedQuoteIdToQuoteIdInterface::class);
        $quoteId = $maskedIdToQuote->execute($maskedId);

        $cartRepository = $this->objectManager->get(CartRepositoryInterface::class);
        $quote = $cartRepository->get($quoteId);
        $cartRepository->delete($quote);
    }
}
