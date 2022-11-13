<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MultipleWishlist\Block\Customer\Wishlist;

use Magento\Customer\Model\Session;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Helper\Xpath;
use Magento\TestFramework\MultipleWishlist\Model\GetCustomerWishListByName;
use Magento\Wishlist\Model\Wishlist;
use PHPUnit\Framework\TestCase;

/**
 * Test for wish list management block.
 *
 * @magentoDbIsolation disabled
 * @magentoAppArea frontend
 */
class ManagementTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var Session */
    private $customerSession;

    /** @var GetCustomerWishListByName */
    private $getCustomerWishListByName;

    /** @var Management */
    private $block;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->customerSession = $this->objectManager->get(Session::class);
        $this->getCustomerWishListByName = $this->objectManager->get(GetCustomerWishListByName::class);
        $this->block = $this->objectManager->get(LayoutInterface::class)->createBlock(Management::class)
            ->setTemplate('Magento_MultipleWishlist::view/management.phtml');
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
    public function testDisplayWishListManagementBlock(): void
    {
        $customerId = 1;
        $this->customerSession->setCustomerId($customerId);
        $firstWishList = $this->getCustomerWishListByName->execute($customerId, 'First Wish List');
        $secondWishList = $this->getCustomerWishListByName->execute($customerId, 'Second Wish List');
        $this->block->getRequest()->setParam('wishlist_id', $firstWishList->getWishlistId());
        $blockHtml = $this->block->toHtml();
        $wishListName = $firstWishList->getName();
        $this->assertWishListSelectBlock($blockHtml, $wishListName, $secondWishList);
        $this->assertWishListTitleBlock($blockHtml, $wishListName);
        $this->assertWishListInfoBlock($blockHtml, 'public', 2);
        $this->assertWishListToolbarBlock($blockHtml);
    }

    /**
     * @magentoConfigFixture current_store wishlist/general/multiple_enabled 1
     * @magentoConfigFixture current_store wishlist/wishlist_link/use_qty 0
     * @magentoDataFixture Magento/Wishlist/_files/wishlist_with_product_qty_three.php
     *
     * @return void
     */
    public function testDisplayNumberOfItemsInWishList(): void
    {
        $this->markTestSkipped('Test is blocked by issue MC-31595');
        $this->customerSession->setCustomerId(1);
        $this->assertWishListInfoBlock($this->block->toHtml(), 'private', 3);
    }

    /**
     * Assert wish list select block.
     *
     * @param string $html
     * @param string $wishListName
     * @param Wishlist $secondWishList
     * @return void
     */
    private function assertWishListSelectBlock(string $html, string $wishListName, Wishlist $secondWishList): void
    {
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(
                sprintf("//ul[contains(@class, 'wishlist-select-items')]"
                    . "/li[contains(@class, 'current')]/span[contains(text(), '%s')]", $wishListName),
                $html
            ),
            sprintf('Current wish list with name "%s" wasn\'t found in top navigation menu.', $wishListName)
        );
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(
                sprintf(
                    "//ul[contains(@class, 'wishlist-select-items')]/li[contains(@class, 'item')]"
                    . "/a[contains(@href, 'wishlist/index/index/wishlist_id/%s') and contains(text(), '%s')]",
                    $secondWishList->getWishlistId(),
                    $secondWishList->getName()
                ),
                $html
            ),
            sprintf('Wish list with name "%s" wasn\'t found in top navigation menu.', $secondWishList->getName())
        );
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(
                "//ul[contains(@class, 'wishlist-select-items')]/li[contains(@class, 'wishlist-add')]"
                . "//span[contains(text(), '" . __('Create New Wish List') . "')]",
                $html
            ),
            '"Create New Wish List" button was not found.'
        );
    }

    /**
     * Assert wish list title block.
     *
     * @param string $html
     * @param string $wishListName
     * @return void
     */
    private function assertWishListTitleBlock(string $html, string $wishListName): void
    {
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(
                sprintf("//div[contains(@class, 'wishlist-title')]/strong[contains(text(), '%s')]", $wishListName),
                $html
            ),
            sprintf('Title for wish list "%s" wasn\'t found.', $wishListName)
        );
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(
                "//div[contains(@class, 'wishlist-title')]"
                . "/a[contains(@class, 'edit') and contains(text(), '" . __('Edit') . "')]",
                $html
            ),
            sprintf('Edit wish list \"%s\" button wasn\'t found.', $wishListName)
        );
    }

    /**
     * Assert wish list info block.
     *
     * @param string $html
     * @param string $visibility
     * @param int $itemCount
     * @return void
     */
    private function assertWishListInfoBlock(string $html, string $visibility, int $itemCount): void
    {
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(
                sprintf(
                    "//div[contains(@class, 'wishlist-info')]"
                    . "/div[contains(@class, '%s') and contains(text(), '%s')]",
                    $visibility,
                    __(ucfirst($visibility) . ' Wish List')
                ),
                $html
            ),
            sprintf('Wish list visibility wasn\'t found or not equals to %s.', $visibility)
        );
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(
                "//div[contains(@class, 'wishlist-info')]/span[contains(@class, 'counter')"
                . " and contains(text(), '" . __('%1 items in wish list', $itemCount) . "')]",
                $html
            ),
            sprintf('Element wish list items count wasn\'t found or not equals to %s.', $itemCount)
        );
    }

    /**
     * Assert wish list toolbar block.
     *
     * @param string $html
     * @return void
     */
    private function assertWishListToolbarBlock(string $html): void
    {
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(
                "//div[contains(@class, 'wishlist-toolbar-select')]/input[contains(@id, 'wishlist-select-all')]"
                . "/following-sibling::label[contains(text(), '" . __('Select all') . "')]",
                $html
            ),
            '"Select all" checkbox wasn\'t found in toolbar menu.'
        );
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(
                "//div[contains(@class, 'wishlist-toolbar-actions')]/div[contains(@class, 'move')]"
                . "//span[contains(text(), '" . __('Move Selected to Wish List') . "')]",
                $html
            ),
            'Dropdown "Move selected to Wish List" wasn\'t found in toolbar menu.'
        );
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(
                "//div[contains(@class, 'wishlist-toolbar-actions')]/div[contains(@class, 'copy')]"
                . "//span[contains(text(), '" . __('Copy Selected to Wish List') . "')]",
                $html
            ),
            'Dropdown "Copy selected to Wish List" wasn\'t found in toolbar menu.'
        );
    }
}
