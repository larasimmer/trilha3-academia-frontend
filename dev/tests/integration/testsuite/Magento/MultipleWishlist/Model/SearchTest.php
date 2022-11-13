<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MultipleWishlist\Model;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\MultipleWishlist\Model\Search\Strategy\EmailFactory;
use Magento\MultipleWishlist\Model\Search\Strategy\NameFactory;
use Magento\MultipleWishlist\Model\SearchFactory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Tests for wish lists search results.
 *
 * @magentoDbIsolation enabled
 * @magentoDataFixture Magento/MultipleWishlist/_files/wishlists_with_two_items.php
 */
class SearchTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var SearchFactory */
    private $searchFactory;

    /** @var NameFactory */
    private $strategyNameFactory;

    /** @var EmailFactory */
    private $strategyEmailFactory;

    /** @var CustomerRepositoryInterface */
    private $customerRepositoryInterface;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->searchFactory = $this->objectManager->get(SearchFactory::class);
        $this->strategyNameFactory = $this->objectManager->get(NameFactory::class);
        $this->strategyEmailFactory = $this->objectManager->get(EmailFactory::class);
        $this->customerRepositoryInterface = $this->objectManager->get(CustomerRepositoryInterface::class);
    }

    /**
     * @return void
     */
    public function testSearchResultsByCustomerEmail(): void
    {
        $customer = $this->customerRepositoryInterface->get('customer@example.com');
        $strategy = $this->strategyEmailFactory->create();
        $strategy->setSearchParams(['email' => $customer->getEmail()]);
        $search = $this->searchFactory->create();
        $results = $search->getResults($strategy);
        $this->assertCount(1, $results->getItems());
        $this->assertEquals(1, $results->getSize());
    }

    /**
     * @return void
     */
    public function testSearchResultsByCustomerName(): void
    {
        $customer = $this->customerRepositoryInterface->get('customer@example.com');
        $strategy = $this->strategyNameFactory->create();
        $strategy->setSearchParams(['firstname' => $customer->getFirstname(), 'lastname' => $customer->getLastname()]);
        $search = $this->searchFactory->create();
        $results = $search->getResults($strategy);
        $this->assertCount(1, $results->getItems());
        $this->assertEquals(1, $results->getSize());
    }
}
