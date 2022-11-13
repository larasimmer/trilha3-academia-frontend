<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerSegment\Model\Segment\Condition\Product\Combine;

use Magento\Framework\ObjectManagerInterface;
use Magento\Quote\Model\Quote;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class ListCombineTest extends TestCase
{
    /**
     * @var ListCombine
     */
    private $model;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->model = $this->objectManager->get(ListCombine::class);
    }

    /**
     * Tests isSatisfiedBy method with a visitor quote
     *
     * @magentoDataFixture Magento/Sales/_files/guest_quote_with_addresses.php
     * @return void
     */
    public function testIsSatisfiedByWithVisitorQuote(): void
    {
        $this->model->setValue('shopping_cart')
            ->setOperator('==');
        $websiteId = '1';
        /** @var Quote $quote */
        $quote = $this->objectManager->get(Quote::class);
        $quote->load('guest_quote', 'reserved_order_id');
        $matchingParams = [
            'quote_id' => $quote->getEntityId()
        ];
        $this->assertTrue($this->model->isSatisfiedBy(null, $websiteId, $matchingParams));
    }

    /**
     * Tests isSatisfiedBy method with a customer
     *
     * @magentoDataFixture Magento/Sales/_files/quote_with_customer.php
     * @return void
     */
    public function testIsSatisfiedByWithLoggedInCustomer(): void
    {
        $this->model->setValue('shopping_cart')
            ->setOperator('==');
        $websiteId = '1';
        /** @var Quote $quote */
        $quote = $this->objectManager->get(Quote::class);
        $quote->load('test01', 'reserved_order_id');
        $customerId = $quote->getCustomerId();
        $matchingParams = [
            'quote_id' => $quote->getEntityId()
        ];
        $this->assertTrue($this->model->isSatisfiedBy($customerId, $websiteId, $matchingParams));
        $matchingParams = [
            'quote_id' => 0
        ];
        $this->assertTrue($this->model->isSatisfiedBy($customerId, $websiteId, $matchingParams));
    }
}
