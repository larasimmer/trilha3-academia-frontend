<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Controller\Adminhtml\Index;

use Magento\Backend\Model\Session;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\View\LayoutInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\TestFramework\Quote\Model\GetQuoteByReservedOrderId;
use Magento\TestFramework\TestCase\AbstractBackendController;
use Magento\TestFramework\Wishlist\Model\GetWishlistByCustomerId;

/**
 * Checks blocks load controller
 *
 * @see \Magento\AdvancedCheckout\Controller\Adminhtml\Index\LoadBlock
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class LoadBlockTest extends AbstractBackendController
{
    /** @var ProductRepositoryInterface */
    private $productRepository;

    /** @var LayoutInterface */
    private $layout;

    /** @var Session */
    private $session;

    /** @var CartRepositoryInterface */
    private $cartRepository;

    /** @var OrderInterfaceFactory */
    private $orderFactory;

    /** @var CustomerRepositoryInterface */
    private $customerRepository;

    /** @var  GetWishlistByCustomerId */
    private $getWishListByCustomerId;

    /** @var GetQuoteByReservedOrderId */
    private $getQuoteByReservedOrderId;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->productRepository = $this->_objectManager->get(ProductRepositoryInterface::class);
        $this->productRepository->cleanCache();
        $this->layout = $this->_objectManager->get(LayoutInterface::class);
        $this->session = $this->_objectManager->get(Session::class);
        $this->cartRepository = $this->_objectManager->get(CartRepositoryInterface::class);
        $this->orderFactory = $this->_objectManager->get(OrderInterfaceFactory::class);
        $this->customerRepository = $this->_objectManager->get(CustomerRepositoryInterface::class);
        $this->getWishListByCustomerId = $this->_objectManager->get(GetWishlistByCustomerId::class);
        $this->getQuoteByReservedOrderId = $this->_objectManager->get(GetQuoteByReservedOrderId::class);
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDataFixture Magento/Catalog/_files/simple_product_disabled.php
     *
     * @return void
     */
    public function testExecuteWithError(): void
    {
        $productId = $this->productRepository->get('product_disabled')->getId();
        $this->dispatchRequestWithData(
            ['store' => 1, 'customer' => 1],
            $this->preparePost('product_to_add', (int)$productId, 1)
        );
        $errors = $this->layout->getBlock('messages')->getMessagesByType('error');
        $this->assertNotEmpty($errors);
        $this->assertEquals(
            __('Product that you are trying to add is not available.'),
            reset($errors)->getText()
        );
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDataFixture Magento/Catalog/_files/second_product_simple.php
     *
     * @dataProvider sourcesPostDataProvider
     *
     * @param string $source
     * @return void
     */
    public function testExecuteFromDifferentSources(string $source): void
    {
        $this->dispatchRequestWithData(
            ['store' => 1, 'customer' => 1],
            $this->preparePost($source, 6, 1)
        );
        $this->assertSuccessFromSources(1, 'simple2');
    }

    /**
     * @return array
     */
    public function sourcesPostDataProvider(): array
    {
        return [
            'recently_viewed' => ['rviewed'],
            'in_comparation_list' => ['compared'],
            'recently_compared' => ['rcompared'],
            'products_to_add' => ['product_to_add'],
        ];
    }

    /**
     * @magentoDbIsolation disabled
     *
     * @magentoDataFixture Magento/Wishlist/_files/wishlist_with_simple_product.php
     *
     * @return void
     */
    public function testExecuteFromWishListSource(): void
    {
        $customerId = 1;
        $item = $this->getWishListByCustomerId->getItemBySku($customerId, 'simple-1');
        $this->dispatchRequestWithData(
            ['store' => 1, 'customer' => $customerId],
            $this->preparePost('wishlist', (int)$item->getId(), 1)
        );
        $this->assertSuccessFromSources($customerId, 'simple-1');
    }

    /**
     * @magentoDbIsolation disabled
     *
     * @magentoDataFixture Magento/Sales/_files/customer_order_with_two_items.php
     *
     * @return void
     */
    public function testExecuteFromOrderedItemsSource(): void
    {
        $customerId = $this->customerRepository->get('customer_uk_address@test.com')->getId();
        $order = $this->orderFactory->create()->loadByIncrementId('100000555');
        $item = $order->getAllItems()[0];
        $this->dispatchRequestWithData(
            ['store' => 1, 'customer' => $customerId],
            $this->preparePost('ordered', (int)$item->getId(), 1)
        );
        $this->assertSuccessFromSources((int)$customerId, 'simple-1');
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDataFixture Magento/Catalog/_files/second_product_simple.php
     *
     * @return void
     */
    public function testExecuteAsJson(): void
    {
        $expectedHandles = [
            'checkout_index_manage_load_block_items',
            'checkout_index_manage_load_block_errors',
            'checkout_index_manage_load_block_json',
        ];
        $this->dispatchRequestWithData(
            [
                'store' => 1,
                'customer' => 1,
                'json' => 1,
                'configure_complex_list_types' => 'wishlist',
            ]
        );
        $this->assertHandles($expectedHandles);
    }

    /**
     * @return void
     */
    public function testExecuteWithRedirect(): void
    {
        $this->dispatchRequestWithData(
            [
                'store' => 1,
                'customer' => 1,
                'as_js_varname' => 'iFrameResponse',
                'configure_complex_list_types' => 'wishlist',
            ]
        );
        $this->assertRedirect($this->stringContains('checkout/index/showUpdateResult'));
        $this->assertNotNull($this->session->getUpdateResult());
    }

    /**
     * @magentoDbIsolation disabled
     *
     * @magentoDataFixture Magento/Checkout/_files/quote_with_customer_without_address.php
     *
     * @return void
     */
    public function testMoveItemToWishlist(): void
    {
        $quote = $this->getQuoteByReservedOrderId->execute('test_order_with_customer_without_address');
        $itemId = $quote->getItemsCollection()->getFirstItem()->getId();
        $post = [
            'update_items' => '1',
            'item' => [
                $itemId => [
                    'qty' => '1',
                    'action' => 'wishlist',
                ],
            ],
        ];
        $this->dispatchRequestWithData(
            ['store' => 1, 'customer' => 1],
            $post,
            []
        );
        $this->assertNotNull($this->getWishListByCustomerId->getItemBySku(1, 'simple2'));
    }

    /**
     * Dispatch request with params
     *
     * @param array $params
     * @param array $post
     * @param array $additionalParams
     * @return void
     */
    private function dispatchRequestWithData(
        array $params,
        array $post = [],
        $additionalParams = ['block' => 'items,errors']
    ): void {
        $params = array_merge($params, $additionalParams);
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue($post);
        $this->getRequest()->setParams($params);
        $this->dispatch('backend/checkout/index/loadBlock/');
    }

    /**
     * Prepare post
     *
     * @param string $source
     * @param int $productId
     * @param int $qty
     * @return array
     */
    private function preparePost(string $source, int $productId, int $qty): array
    {
        return [
            'list' => [
                $source => [
                    'item' => [
                        $productId => [
                            'qty' => $qty,
                        ],
                    ],
                ],
            ],
            'configure_complex_list_types' => $source,
        ];
    }

    /**
     * Assert that all expected handles were applied
     *
     * @param array $expectedHandles
     * @return void
     */
    private function assertHandles(array $expectedHandles): void
    {
        $handles = $this->layout->getUpdate()->getHandles();
        foreach ($expectedHandles as $expectedHandle) {
            $this->assertContains($expectedHandle, $handles);
        }
    }

    /**
     * Assert that product was succesfully added to the cart
     *
     * @param int $customerId
     * @param string $expectedSku
     * @param array $expectedHandles
     * @return void
     */
    private function assertSuccessFromSources(
        int $customerId,
        string $expectedSku,
        array $expectedHandles = [
            'checkout_index_manage_load_block_items',
            'checkout_index_manage_load_block_errors',
            'checkout_index_manage_load_block_plain',
        ]
    ): void {
        $quote = $this->cartRepository->getForCustomer($customerId);
        $items = $quote->getAllItems();
        $this->assertNotEmpty($items);
        $item = reset($items);
        $this->assertEquals($expectedSku, $item->getSku());
        $this->assertHandles($expectedHandles);
    }
}
