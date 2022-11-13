<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MultipleWishlist\Controller\Index;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\MessageInterface;
use Magento\TestFramework\MultipleWishlist\Model\GetCustomerWishListByName;
use Magento\TestFramework\TestCase\AbstractController;
use Magento\TestFramework\Wishlist\Model\GetWishlistByCustomerId;

/**
 * Test for delete wish list.
 *
 * @magentoDbIsolation enabled
 * @magentoAppArea frontend
 */
class DeletewishlistTest extends AbstractController
{
    /** @var Session */
    private $customerSession;

    /** @var GetWishlistByCustomerId */
    private $getWishlistByCustomerId;

    /** @var GetCustomerWishListByName */
    private $getCustomerWishListByName;

    /** @var Escaper */
    private $escaper;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->customerSession = $this->_objectManager->get(Session::class);
        $this->getWishlistByCustomerId = $this->_objectManager->get(GetWishlistByCustomerId::class);
        $this->getCustomerWishListByName = $this->_objectManager->get(GetCustomerWishListByName::class);
        $this->escaper = $this->_objectManager->get(Escaper::class);
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
     * @magentoConfigFixture current_store wishlist/general/multiple_enabled 1
     * @magentoDataFixture Magento/MultipleWishlist/_files/wishlists_with_two_items.php
     *
     * @return void
     */
    public function testDeleteWishList(): void
    {
        $this->customerSession->setCustomerId(1);
        $wishList = $this->getCustomerWishListByName->execute(1, 'Second Wish List');
        $params = ['wishlist_id' => $wishList->getWishlistId()];
        $this->performDeleteWishListRequest($params);
        $this->assertRedirect($this->stringContains('wishlist/'));
        $message = sprintf('Wish List "%s" has been deleted.', $wishList->getName());
        $this->assertSessionMessages($this->equalTo([(string)__($message)]), MessageInterface::TYPE_SUCCESS);
        $this->expectException(NoSuchEntityException::class);
        $this->getCustomerWishListByName->execute(1, 'Second Wish List');
    }

    /**
     * @magentoConfigFixture current_store wishlist/general/multiple_enabled 1
     * @magentoDataFixture Magento/Wishlist/_files/wishlist.php
     *
     * @return void
     */
    public function testDeleteDefaultWishList(): void
    {
        $this->customerSession->setCustomerId(1);
        $wishList = $this->getWishlistByCustomerId->execute(1);
        $params = ['wishlist_id' => $wishList->getWishlistId()];
        $this->performDeleteWishListRequest($params);
        $this->assertRedirect($this->stringContains('wishlist/'));
        $message = 'The default wish list can\'t be deleted.';
        $this->assertSessionMessages($this->equalTo([(string)__($message)]), MessageInterface::TYPE_ERROR);
    }

    /**
     * @magentoConfigFixture current_store wishlist/general/multiple_enabled 1
     * @magentoDataFixture Magento/Customer/_files/customer.php
     *
     * @return void
     */
    public function testDeleteNotExistingWishList(): void
    {
        $this->customerSession->setCustomerId(1);
        $params = ['wishlist_id' => 989];
        $this->performDeleteWishListRequest($params);
        $this->assertRedirect($this->stringContains('wishlist/'));
        $messages = [
            (string)__($this->escaper->escapeHtml('The requested Wish List doesn\'t exist.')),
            (string)__('Page not found.'),
        ];
        $this->assertSessionMessages($this->equalTo($messages), MessageInterface::TYPE_ERROR);
    }

    /**
     * Perform delete wish list request.
     *
     * @param array $params
     * @return void
     */
    private function performDeleteWishListRequest(array $params): void
    {
        $this->getRequest()->setParams($params);
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->dispatch('wishlist/index/deletewishlist');
    }
}
