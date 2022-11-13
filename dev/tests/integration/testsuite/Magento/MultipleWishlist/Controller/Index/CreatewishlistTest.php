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
 * Tests for create wish list.
 *
 * @magentoDbIsolation enabled
 * @magentoAppArea frontend
 */
class CreatewishlistTest extends AbstractController
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
        $this->customerSession->setCustomerId(null);

        parent::tearDown();
    }

    /**
     * @magentoConfigFixture current_store wishlist/general/multiple_enabled 1
     * @magentoDataFixture Magento/Customer/_files/customer.php
     *
     * @return void
     */
    public function testCreateWishList(): void
    {
        $this->customerSession->setCustomerId(1);
        $params = ['name' => 'New Wish List', 'visibility' => 'on'];
        $this->performCreateWishListRequest($params);
        $message = sprintf('Wish list "%s" was saved.', $params['name']);
        $this->assertSessionMessages($this->equalTo([(string)__($message)]), MessageInterface::TYPE_SUCCESS);
        $wishList = $this->getWishlistByCustomerId->execute(1);
        $this->assertRedirect(
            $this->stringContains('wishlist/index/index/wishlist_id/' . $wishList->getWishlistId())
        );
        $this->assertEquals($params['name'], $wishList->getName());
        $this->assertEquals(1, $wishList->getVisibility());
    }

    /**
     * @magentoConfigFixture current_store wishlist/general/multiple_enabled 1
     * @magentoDataFixture Magento/Customer/_files/customer.php
     *
     * @return void
     */
    public function testCreateWishListAjax(): void
    {
        $this->customerSession->setCustomerId(1);
        $params = ['name' => 'New Wish List', 'isAjax' => true];
        $this->performCreateWishListRequest($params);
        $result = $this->json->unserialize($this->getResponse()->getBody());
        $message = sprintf('Wish list "%s" was saved.', $params['name']);
        $this->assertSessionMessages($this->equalTo([(string)__($message)]), MessageInterface::TYPE_SUCCESS);
        $wishList = $this->getWishlistByCustomerId->execute(1);
        $this->assertEquals($wishList->getWishlistId(), $result['wishlist_id']);
        $this->assertStringContainsString(
            'wishlist/index/index/wishlist_id/' . $wishList->getWishlistId(),
            $result['redirect']
        );
    }

    /**
     * Perform create wish list request.
     *
     * @param array $params
     * @return void
     */
    private function performCreateWishListRequest(array $params): void
    {
        $this->getRequest()->setParams($params);
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->dispatch('wishlist/index/createwishlist');
    }
}
