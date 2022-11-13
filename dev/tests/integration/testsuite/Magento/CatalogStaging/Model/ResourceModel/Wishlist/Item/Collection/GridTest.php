<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Model\ResourceModel\Wishlist\Item\Collection;

use Magento\Customer\Controller\RegistryConstants;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Registry;
use Magento\Wishlist\Model\ResourceModel\Item\Collection\Grid as GridCollection;
use Magento\Wishlist\Model\ResourceModel\Item\Collection\GridFactory as GridCollectionFactory;
use PHPUnit\Framework\TestCase;

/**
 * Test of managing wishlist item collection with CatalogStaging functionality.
 *
 * @magentoAppArea adminhtml
 */
class GridTest extends TestCase
{
    /**
     * @var Registry
     */
    private $registryManager;

    /**
     * @var GridCollectionFactory
     */
    private $gridCollectionFactory;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->registryManager = ObjectManager::getInstance()->get(Registry::class);
        $this->gridCollectionFactory = ObjectManager::getInstance()->get(GridCollectionFactory::class);
    }

    /**
     * Check that wishlist item collection is filtered correctly after creating product with staged changes.
     *
     * @magentoDbIsolation disabled
     * @magentoDataFixture Magento/CatalogStaging/_files/simple_product_staged_changes.php
     * @magentoDataFixture Magento/Wishlist/_files/wishlist_with_simple_product.php
     */
    public function testAddFieldToFilter(): void
    {
        $productName = 'Simple Product';
        $this->registryManager->register(RegistryConstants::CURRENT_CUSTOMER_ID, 1);

        $itemsBeforeFilter = $this->getWishlistItemCollection()->getSize();

        /** @var GridCollection $gridCollection */
        $gridCollection = $this->getWishlistItemCollection()
            ->addFieldToFilter('product_name', ['like' => $productName]);
        $itemsAfterFilter = $gridCollection->getSize();

        $this->assertEquals($itemsBeforeFilter, $itemsAfterFilter);
        $this->assertEquals($productName, $gridCollection->getFirstItem()->getProductName());
    }

    /**
     * Retrieve instance of wishlist item grid collection.
     *
     * @return GridCollection
     */
    private function getWishlistItemCollection(): GridCollection
    {
        return $this->gridCollectionFactory->create();
    }
}
