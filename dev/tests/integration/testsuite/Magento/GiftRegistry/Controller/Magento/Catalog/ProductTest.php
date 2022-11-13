<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Controller\Magento\Catalog;

/**
 * Check "add gift registry" link available in product view
 */
class ProductTest extends \Magento\TestFramework\TestCase\AbstractController
{
    /**
     *
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     */
    public function testViewAction()
    {
        /** @var $objectManager \Magento\TestFramework\ObjectManager */
        $objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        /** @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepository */
        $productRepository = $objectManager->create(\Magento\Catalog\Api\ProductRepositoryInterface::class);
        $product = $productRepository->get('simple');

        $this->getRequest()->setParam('options', \Magento\GiftRegistry\Block\Product\View::FLAG);
        $this->dispatch('catalog/product/view/id/' . $product->getId());
        $body = $this->getResponse()->getBody();
        $this->assertStringContainsString('<span>Add to Gift Registry</span>', $body);
        $this->assertStringContainsString(
            'http\u003A\u002F\u002Flocalhost\u002Findex.php\u002Fgiftregistry\u002Findex\u002Fcart\u002F',
            $body
        );
    }

    /**
     * @magentoDataFixture Magento/Bundle/_files/product.php
     */
    public function testViewActionBundle()
    {
        /** @var $objectManager \Magento\TestFramework\ObjectManager */
        $objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        /** @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepository */
        $productRepository = $objectManager->create(\Magento\Catalog\Api\ProductRepositoryInterface::class);
        $product = $productRepository->get('bundle-product');

        $this->getRequest()->setParam('options', \Magento\GiftRegistry\Block\Product\View::FLAG);
        $this->dispatch('catalog/product/view/id/' . $product->getId());
        $body = $this->getResponse()->getBody();
        $this->assertStringContainsString('<span>Customize and Add to Gift Registry</span>', $body);
        $this->assertStringContainsString('<span>Add to Gift Registry</span>', $body);
        $this->assertStringContainsString(
            'http\u003A\u002F\u002Flocalhost\u002Findex.php\u002Fgiftregistry\u002Findex\u002Fcart\u002F',
            $body
        );
    }
}
