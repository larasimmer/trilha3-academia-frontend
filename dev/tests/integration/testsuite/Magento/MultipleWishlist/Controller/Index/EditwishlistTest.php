<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MultipleWishlist\Controller\Index;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Message\MessageInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\TestFramework\TestCase\AbstractController;
use Magento\TestFramework\Wishlist\Model\GetWishlistByCustomerId;

/**
 * Tests for edit wish list.
 *
 * @magentoDbIsolation enabled
 * @magentoAppArea frontend
 * @magentoDataFixture Magento/Customer/_files/customer.php
 */
class EditwishlistTest extends AbstractController
{
    /** @var Session */
    private $customerSession;

    /** @var SerializerInterface */
    private $json;

    /** @var GetWishlistByCustomerId */
    private $getWishlistByCustomerId;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->customerSession = $this->_objectManager->get(Session::class);
        $this->json = $this->_objectManager->get(SerializerInterface::class);
        $this->getWishlistByCustomerId = $this->_objectManager->get(GetWishlistByCustomerId::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        $this->customerSession->logout();

        parent::tearDown();
    }

    /**
     * @magentoConfigFixture current_store wishlist/general/multiple_enabled 1
     * @magentoDataFixture Magento/Wishlist/_files/wishlist.php
     *
     * @return void
     */
    public function testEditWishList(): void
    {
        $this->customerSession->loginById(1);
        $wishList = $this->getWishlistByCustomerId->execute(1);
        $params = ['wishlist_id' => $wishList->getId(), 'name' => 'New Name Wish List', 'visibility' => 'on'];
        $this->performEditWishListRequest($params);
        $message = [(string)__('Wish list "%1" was saved.', $params['name'])];
        $this->assertSessionMessages($this->equalTo($message), MessageInterface::TYPE_SUCCESS);
        $this->assertRedirect($this->stringContains('wishlist/index/'));
        $updatedWishList = $this->getWishlistByCustomerId->execute(1);
        $this->assertEquals(1, $updatedWishList->getVisibility());
    }

    /**
     * @magentoConfigFixture current_store wishlist/general/multiple_enabled 1
     * @magentoDataFixture Magento/Wishlist/_files/wishlist.php
     *
     * @return void
     */
    public function testEditNotExistingWishList(): void
    {
        $this->customerSession->loginById(1);
        $params = ['wishlist_id' => 989];
        $this->performEditWishListRequest($params);
        $messages = [
            (string)__('The wish list is not assigned to your account and can\'t be edited.'),
            (string)__('Could not create a wish list.'),
        ];
        $this->assertSessionMessages($this->equalTo(($messages)), MessageInterface::TYPE_ERROR);
        $this->assertRedirect($this->stringContains('wishlist/index/'));
    }

    /**
     * @magentoConfigFixture current_store wishlist/general/multiple_enabled 1
     * @magentoDataFixture Magento/Wishlist/_files/wishlist.php
     *
     * @return void
     */
    public function testEditWishListAjax(): void
    {
        $this->customerSession->loginById(1);
        $wishList = $this->getWishlistByCustomerId->execute(1);
        $params = ['wishlist_id' => $wishList->getId(), 'name' => 'New Name Wish List', 'isAjax' => true];
        $this->performEditWishListRequest($params);
        $message = [(string)__('Wish list "%1" was saved.', $params['name'])];
        $this->assertSessionMessages($this->equalTo($message), MessageInterface::TYPE_SUCCESS);
        $result = $this->json->unserialize($this->getResponse()->getBody());
        $this->assertEquals($params['wishlist_id'], $result['wishlist_id']);
        $this->assertStringContainsString(
            'wishlist/index/index/wishlist_id/' . $params['wishlist_id'],
            $result['redirect']
        );
    }

    /**
     * @magentoConfigFixture current_store wishlist/general/multiple_enabled 1
     * @magentoDataFixture Magento/Wishlist/_files/wishlist.php
     *
     * @return void
     */
    public function testEditNotExistingWishListAjax(): void
    {
        $this->customerSession->loginById(1);
        $params = ['wishlist_id' => 989, 'isAjax' => true];
        $this->performEditWishListRequest($params);
        $result = $this->json->unserialize($this->getResponse()->getBody());
        $messages = [
            (string)__('The wish list is not assigned to your account and can\'t be edited.'),
            (string)__('Could not create a wish list.'),
        ];
        $this->assertSessionMessages($this->equalTo(($messages)), MessageInterface::TYPE_ERROR);
        $this->assertStringContainsString('wishlist/index', $result['redirect']);
    }

    /**
     * Perform edit wish list request.
     *
     * @param array $params
     * @return void
     */
    private function performEditWishListRequest(array $params): void
    {
        $this->getRequest()->setParams($params)->setMethod(HttpRequest::METHOD_POST);
        $this->dispatch('wishlist/index/editwishlist');
    }
}
