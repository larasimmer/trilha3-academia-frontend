<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Controller\View;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Data\Form\FormKey;
use Magento\TestFramework\Quote\Model\GetQuoteByReservedOrderId;
use Magento\TestFramework\TestCase\AbstractController;

/**
 * Checks update behavior for a shopping cart with gift registry item.
 */
class UpdateCartTest extends AbstractController
{
    /**
     * @var FormKey
     */
    private $formKey;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var GetQuoteByReservedOrderId|mixed
     */
    private $getQuoteByReservedOrderId;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->formKey = $this->_objectManager->get(FormKey::class);
        $this->productRepository = $this->_objectManager->get(ProductRepositoryInterface::class);
        $this->getQuoteByReservedOrderId = $this->_objectManager->get(GetQuoteByReservedOrderId::class);
    }

    /**
     * Tests that quote item contains gift registry item after an update.
     *
     * @magentoAppArea frontend
     * @magentoDataFixture Magento/GiftRegistry/_files/quote_with_gift_registry_item.php
     */
    public function testUpdateCart()
    {
        $product = $this->productRepository->get('simple');
        $quoteItem = current($this->getQuoteByReservedOrderId->execute('test_cart_gift_registry_item')->getItems());

        $postData = [
            'product' => $product->getId(),
            'selected_configurable_option' => '',
            'related_product' => '',
            'item' => $quoteItem->getId(),
            'form_key' => $this->formKey->getFormKey(),
            'qty' => '2',
        ];

        $this->dispatchUpdateItemOptionsRequest($postData);
        $this->assertTrue($this->getResponse()->isRedirect());
        $this->assertRedirect($this->stringContains('/checkout/cart/'));

        $updatedQuoteItem = current(
            $this->getQuoteByReservedOrderId->execute('test_cart_gift_registry_item')->getItems()
        );
        $this->assertTrue($updatedQuoteItem->hasGiftregistryItemId());
    }

    /**
     * Perform request for updating product options in a quote item.
     *
     * @param array $postData
     */
    private function dispatchUpdateItemOptionsRequest(array $postData): void
    {
        $this->getRequest()->setPostValue($postData);
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->dispatch('checkout/cart/updateItemOptions/id/' . $postData['item']);
    }
}
