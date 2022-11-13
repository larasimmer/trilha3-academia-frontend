<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Observer;

use Magento\Customer\Model\AttributeFactory;
use Magento\Customer\Model\Customer;
use Magento\CustomerCustomAttributes\Model\ResourceModel\Sales\Order as OrderResource;
use Magento\CustomerCustomAttributes\Model\ResourceModel\Sales\Quote as QuoteResource;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Test for delete customer custom attribute observer.
 *
 * @see \Magento\CustomerCustomAttributes\Observer\EnterpriseCustomerAttributeDelete
 * @magentoDbIsolation disabled
 */
class EnterpriseCustomerAttributeDeleteTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var AttributeFactory */
    private $attributeFactory;

    /** @var ManagerInterface */
    private $eventManager;

    /** @var QuoteResource */
    private $quoteResource;

    /** @var OrderResource */
    private $orderResource;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->attributeFactory = $this->objectManager->get(AttributeFactory::class);
        $this->eventManager = $this->objectManager->get(ManagerInterface::class);
        $this->quoteResource = $this->objectManager->get(QuoteResource::class);
        $this->orderResource = $this->objectManager->get(OrderResource::class);
    }

    /**
     * @magentoDataFixture Magento/CustomerCustomAttributes/_files/customer_attribute_type_select.php
     *
     * @return void
     */
    public function testExecute(): void
    {
        $attribute = $this->attributeFactory->create()
            ->loadByCode(Customer::ENTITY, 'customer_attribute_type_select');
        $this->assertNotNull($attribute->getId());
        $this->eventManager->dispatch(
            'magento_customercustomattributes_attribute_delete',
            ['attribute' => $attribute]
        );
        $columnInQuoteTable = $this->quoteResource->getConnection()
            ->tableColumnExists($this->quoteResource->getMainTable(), 'customer_' . $attribute->getAttributeCode());
        $this->assertFalse(
            $columnInQuoteTable,
            sprintf('Column for attribute still exist in "%s" table.', $this->quoteResource->getMainTable())
        );
        $columnInOrderTable = $this->orderResource->getConnection()
            ->tableColumnExists($this->orderResource->getMainTable(), 'customer_' . $attribute->getAttributeCode());
        $this->assertFalse(
            $columnInOrderTable,
            sprintf('Column for attribute still exist in "%s" table.', $this->orderResource->getMainTable())
        );
    }
}
