<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MultipleWishlist\Controller\Index;

use Laminas\Stdlib\Parameters;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Message\MessageInterface;
use Magento\TestFramework\MultipleWishlist\Model\GetCustomerWishListByName;
use Magento\TestFramework\TestCase\AbstractController;

/**
 * Test for add product to wish list.
 *
 * @magentoDbIsolation disabled
 * @magentoAppArea frontend
 */
class AddTest extends AbstractController
{
    /** @var Session */
    private $customerSession;

    /** @var ProductRepositoryInterface */
    private $productRepository;

    /** @var GetCustomerWishListByName */
    private $getCustomerWishListByName;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->customerSession = $this->_objectManager->get(Session::class);
        $this->productRepository = $this->_objectManager->get(ProductRepositoryInterface::class);
        $this->productRepository->cleanCache();
        $this->getCustomerWishListByName = $this->_objectManager->get(GetCustomerWishListByName::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        $this->customerSession->setCustomerId(null);

        parent::tearDown();
    }

    /**
     * @magentoDataFixture Magento/Wishlist/_files/wishlist.php
     * @magentoDataFixture Magento/Catalog/_files/product_simple_duplicated.php
     *
     * @return void
     */
    public function testAddProductAndCreateNewWishList(): void
    {
        $this->prepareReferer();
        $this->customerSession->setCustomerId(1);
        $product = $this->productRepository->get('simple-1');
        $params = ['product' => $product->getId(), 'name' => 'New Wish List'];
        $this->getRequest()->setParams($params)->setMethod(HttpRequest::METHOD_POST);
        $this->dispatch('wishlist/index/add');
        $wishList = $this->getCustomerWishListByName->execute(1, $params['name']);
        $expectedMessages = [
            (string)__(sprintf('Wish list "%s" was saved.', $wishList->getName())),
            (string)__(sprintf("\n%s has been added to your Wish List.", $product->getName())
                . ' Click <a href="http://localhost/test">here</a> to continue shopping.'),
        ];
        $this->assertSessionMessages($this->equalTo($expectedMessages), MessageInterface::TYPE_SUCCESS);
        $this->assertRedirect($this->stringContains('wishlist/index/index/wishlist_id/' . $wishList->getId()));
        $this->assertCount(1, $wishList->getItemCollection());
    }

    /**
     * Prepare referer to test.
     *
     * @return void
     */
    private function prepareReferer(): void
    {
        $parameters = $this->_objectManager->create(Parameters::class);
        $parameters->set('HTTP_REFERER', 'http://localhost/test');
        $this->getRequest()->setServer($parameters);
    }
}
