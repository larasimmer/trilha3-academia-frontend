<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Observer;

use Magento\Customer\Model\Attribute;
use Magento\CustomerCustomAttributes\Model\ResourceModel\Sales\Order\Address as OrderResource;
use Magento\CustomerCustomAttributes\Model\ResourceModel\Sales\Quote\Address as QuoteResource;
use Magento\Customer\Model\AttributeFactory;
use Magento\Eav\Api\AttributeRepositoryInterface;
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
class EnterpriseCustomerAddressAttributeTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var ManagerInterface */
    private $eventManager;

    /** @var QuoteResource */
    private $quoteResource;

    /** @var OrderResource */
    private $orderResource;

    /** @var AttributeRepositoryInterface */
    private $attributeRepository;

    /** @var AttributeFactory */
    private $attributeFactory;

    /** @var Attribute */
    private $attribute;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->attributeRepository = $this->objectManager->get(AttributeRepositoryInterface::class);
        $this->attributeFactory = $this->objectManager->get(AttributeFactory::class);
        $this->eventManager = $this->objectManager->get(ManagerInterface::class);
        $this->quoteResource = $this->objectManager->get(QuoteResource::class);
        $this->orderResource = $this->objectManager->get(OrderResource::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        if ($this->attribute instanceof Attribute) {
            $this->quoteResource->deleteAttribute($this->attribute);
            $this->orderResource->deleteAttribute($this->attribute);
        }

        parent::tearDown();
    }

    /**
     * @return void
     */
    public function testSaveAttribute(): void
    {
        $this->attribute = $this->attributeFactory->create();
        $this->attribute->setData(['attribute_code' => 'address_test_attribute', 'backend_type' => 'varchar']);
        $this->eventManager->dispatch(
            'customer_entity_attribute_save_commit_after',
            ['attribute' => $this->attribute]
        );
        $columnInQuoteTable = $this->quoteResource->getConnection()
            ->tableColumnExists($this->quoteResource->getMainTable(), 'address_test_attribute');
        $this->assertTrue(
            $columnInQuoteTable,
            sprintf('Column for new attribute not exist in "%s" table.', $this->quoteResource->getMainTable())
        );
        $columnInOrderTable = $this->orderResource->getConnection()
            ->tableColumnExists($this->orderResource->getMainTable(), 'address_test_attribute');
        $this->assertTrue(
            $columnInOrderTable,
            sprintf('Column for new attribute not exist in "%s" table.', $this->orderResource->getMainTable())
        );
    }

    /**
     * @magentoDataFixture Magento/CustomerCustomAttributes/_files/address_custom_attribute_without_transaction.php
     *
     * @return void
     */
    public function testDeleteAttribute(): void
    {
        $attribute = $this->attributeRepository->get('customer_address', 'test_text_code');
        $this->eventManager->dispatch(
            'customer_entity_attribute_delete_commit_after',
            ['attribute' => $attribute]
        );
        $columnInQuoteTable = $this->quoteResource->getConnection()
            ->tableColumnExists($this->quoteResource->getMainTable(), $attribute->getAttributeCode());
        $this->assertFalse(
            $columnInQuoteTable,
            sprintf('Column for attribute still exist in "%s" table.', $this->quoteResource->getMainTable())
        );
        $columnInOrderTable = $this->orderResource->getConnection()
            ->tableColumnExists($this->orderResource->getMainTable(), $attribute->getAttributeCode());
        $this->assertFalse(
            $columnInOrderTable,
            sprintf('Column for attribute still exist in "%s" table.', $this->orderResource->getMainTable())
        );
    }
}
