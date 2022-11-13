<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Controller\Adminhtml\Index;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Exception\InputException;
use Magento\Framework\View\LayoutInterface;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * Checks configure product action
 *
 * @see \Magento\AdvancedCheckout\Controller\Adminhtml\Index\ConfigureProductToAdd
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class ConfigureProductToAddTest extends AbstractBackendController
{
    /** @var ProductRepositoryInterface */
    private $productRepository;

    /** @var LayoutInterface */
    private $layout;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->productRepository = $this->_objectManager->get(ProductRepositoryInterface::class);
        $this->productRepository->cleanCache();
        $this->layout = $this->_objectManager->get(LayoutInterface::class);
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDataFixture Magento/ConfigurableProduct/_files/configurable_product_with_two_child_products.php
     *
     * @return void
     */
    public function testExecute(): void
    {
        $product = $this->productRepository->get('Configurable product');
        $this->dispatchRequestWithData(['store' => 1, 'customer' => 1], ['id' => $product->getId()]);
        $handles = $this->layout->getUpdate()->getHandles();
        $this->assertContains('CATALOG_PRODUCT_COMPOSITE_CONFIGURE', $handles);
        $this->assertContains('catalog_product_view_type_' . $product->getTypeId(), $handles);
    }

    /**
     * @magentoDataFixture Magento/ConfigurableProduct/_files/configurable_product_with_two_child_products.php
     *
     * @return void
     */
    public function testExecuteWithoutCustomer(): void
    {
        $this->expectException(InputException::class);
        $this->expectExceptionMessage(
            (string)__('This customer couldn\'t be found. Verify the customer and try again.')
        );
        $product = $this->productRepository->get('Configurable product');
        $this->dispatchRequestWithData(['store' => 1], ['id' => $product->getId()]);
    }

    /**
     * Dispatch request with params
     *
     * @param array $params
     * @param array $post
     * @return void
     */
    private function dispatchRequestWithData(array $params, array $post): void
    {
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue($post);
        $this->getRequest()->setParams($params);
        $this->dispatch('backend/checkout/index/configureProductToAdd/');
    }
}
