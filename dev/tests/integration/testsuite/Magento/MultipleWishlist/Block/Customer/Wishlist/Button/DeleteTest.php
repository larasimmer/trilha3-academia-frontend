<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MultipleWishlist\Block\Customer\Wishlist\Button;

use Magento\Customer\Model\Session;
use Magento\Framework\Escaper;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\MultipleWishlist\Model\GetCustomerWishListByName;
use PHPUnit\Framework\TestCase;

/**
 * Tests for displaying delete wish list button.
 *
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 * @magentoAppArea frontend
 * @magentoDataFixture Magento/MultipleWishlist/_files/wishlists_with_two_items.php
 */
class DeleteTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var Session */
    private $customerSession;

    /** @var GetCustomerWishListByName */
    private $getCustomerWishListByName;

    /** @var Delete */
    private $block;

    /** @var Escaper */
    private $escaper;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->customerSession = $this->objectManager->get(Session::class);
        $this->getCustomerWishListByName = $this->objectManager->get(GetCustomerWishListByName::class);
        $this->block = $this->objectManager->get(LayoutInterface::class)->createBlock(Delete::class)
            ->setTemplate('Magento_MultipleWishlist::button/delete.phtml');
        $this->escaper = $this->objectManager->get(Escaper::class);
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
     *
     * @return void
     */
    public function testDisplayDeleteWishListButton(): void
    {
        $customerId = 1;
        $this->customerSession->setCustomerId($customerId);
        $secondWishList = $this->getCustomerWishListByName->execute($customerId, 'Second Wish List');
        $this->block->getRequest()->setParam('wishlist_id', $secondWishList->getWishlistId());
        $buttonLabel = $this->escaper->escapeHtmlAttr((string)__('Delete Wish List'));
        $this->assertStringContainsString($buttonLabel, strip_tags($this->block->toHtml()));
    }

    /**
     * @magentoConfigFixture current_store wishlist/general/multiple_enabled 1
     *
     * @return void
     */
    public function testDisplayDeleteButtonForDefaultWishList(): void
    {
        $customerId = 1;
        $this->customerSession->setCustomerId($customerId);
        $firstWishList = $this->getCustomerWishListByName->execute($customerId, 'First Wish List');
        $this->block->getRequest()->setParam('wishlist_id', $firstWishList->getWishlistId());
        $this->assertEmpty($this->block->toHtml());
    }
}
