<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TestFramework\MultipleWishlist\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Wishlist\Model\Wishlist;
use Magento\Wishlist\Model\ResourceModel\Wishlist\CollectionFactory;

/**
 * Load customer wish list by name.
 */
class GetCustomerWishListByName
{
    /** @var CollectionFactory */
    private $wishlistCollectionFactory;

    /**
     * @param CollectionFactory $wishlistCollectionFactory
     */
    public function __construct(CollectionFactory $wishlistCollectionFactory)
    {
        $this->wishlistCollectionFactory = $wishlistCollectionFactory;
    }

    /**
     * Load customer wish list by name.
     *
     * @param int $customerId
     * @param string $wishListName
     * @return Wishlist
     * @throws NoSuchEntityException if wish list with provided name for customer doesn't exist.
     */
    public function execute(int $customerId, string $wishListName): Wishlist
    {
        $wishListCollection = $this->wishlistCollectionFactory->create();
        $wishList = $wishListCollection->filterByCustomerId($customerId)
            ->addFieldToFilter('name', $wishListName)->getFirstItem();
        if (!$wishList->getWishlistId()) {
            throw NoSuchEntityException::doubleField('customer_id', $customerId, 'name', $wishListName);
        }

        return $wishList;
    }
}
