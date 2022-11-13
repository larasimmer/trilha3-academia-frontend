<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MultipleWishlist\Block\Customer\Wishlist\Item\Column;

use Magento\Customer\Model\Session;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Helper\Xpath;
use Magento\TestFramework\Wishlist\Model\GetWishlistByCustomerId;
use PHPUnit\Framework\TestCase;

/**
 * Test for wish list item management block.
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

    /** @var GetWishlistByCustomerId */
    private $getWishlistByCustomerId;

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
        $this->getWishlistByCustomerId = $this->objectManager->get(GetWishlistByCustomerId::class);
        $this->block = $this->objectManager->get(LayoutInterface::class)->createBlock(Management::class)
            ->setTemplate('Magento_MultipleWishlist::item/column/management.phtml');
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
     * @magentoDataFixture Magento/Wishlist/_files/wishlist.php
     *
     * @return void
     */
    public function testDisplayWishListManagementBlock(): void
    {
        $customerId = 1;
        $this->customerSession->setCustomerId($customerId);
        $item = $this->getWishlistByCustomerId->getItemBySku($customerId, 'simple');
        $this->assertNotNull($item);
        $blockHtml = $this->block->setItem($item)->toHtml();
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(
                "//div[contains(@class, 'move')]//span[contains(text(), '" . __('Move to Wish List') . "')]",
                $blockHtml
            ),
            'Button for wish lists dropdown for move item wasn\'t found.'
        );
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(
                "//div[contains(@class, 'copy')]//span[contains(text(), '" . __('Copy to Wish List') . "')]",
                $blockHtml
            ),
            'Button for wish lists dropdown for copy item wasn\'t found.'
        );
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(
                "//div[contains(@class, 'move')]/ul[contains(@class, 'items')]"
                 . "//span[contains(text(), '" . __('Create New Wish List') . "')]",
                $blockHtml
            ),
            'Button for create new wish list and move wish list item wasn\'t found.'
        );
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(
                "//div[contains(@class, 'copy')]/ul[contains(@class, 'items')]"
                . "//span[contains(text(), '" . __('Create New Wish List') . "')]",
                $blockHtml
            ),
            'Button for create new wish list and copy wish list item wasn\'t found.'
        );
    }
}
