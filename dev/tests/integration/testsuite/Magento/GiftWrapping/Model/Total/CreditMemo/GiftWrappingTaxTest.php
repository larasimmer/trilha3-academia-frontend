<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftWrapping\Model\Total\CreditMemo;

use Magento\Sales\Model\Order\InvoiceFactory;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * Gift wrapping tax totals calculate test class
 */
class GiftWrappingTaxTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->objectManager = ObjectManager::getInstance();
    }

    /**
     * Check if gift wrapping tax is counted correctly in case of partial refund
     *
     * @magentoConfigFixture tax/classes/wrapping_tax_class 2
     * @magentoDataFixture Magento/ConfigurableProduct/_files/tax_rule.php
     * @magentoDataFixture Magento/GiftWrapping/_files/invoice_with_giftwrapping.php
     */
    public function testCalculateWithConfigurableProduct(): void
    {
        /** @var InvoiceFactory $invoiceFactory */
        $invoiceFactory = $this->objectManager->get(InvoiceFactory::class);
        $invoice = $invoiceFactory->create()->loadByIncrementId('i100000001');
        $items = $invoice->getOrder()->getAllItems();
        $item = reset($items);
        /** @var \Magento\Sales\Model\Order\CreditmemoFactory $creditMemoFactory */
        $creditMemoFactory = $this->objectManager->get(\Magento\Sales\Model\Order\CreditmemoFactory::class);

        $creditMemo = $creditMemoFactory->createByInvoice($invoice, array_merge($invoice->getData(), ['qtys' => [
            $item->getId() => (int)$item->getQtyOrdered() - 1
        ]]));

        $expectedTaxAmount = $creditMemo->getGwItemsTaxAmount() +
            $creditMemo->getGwTaxAmount() +
            $creditMemo->getGwCardTaxAmount();
        $expectedBaseTaxAmount = $creditMemo->getGwItemsBaseTaxAmount() +
            $creditMemo->getGwBaseTaxAmount() +
            $creditMemo->getGwCardBaseTaxAmount();
        $expectedGrandTotal = $expectedTaxAmount +
            $creditMemo->getGwItemsPrice() +
            $creditMemo->getGwPrice() +
            $creditMemo->getGwCardPrice() +
            $creditMemo->getSubtotal();
        $expectedBaseGrandTotal= $expectedBaseTaxAmount +
            $creditMemo->getGwItemsBasePrice() +
            $creditMemo->getGwBasePrice() +
            $creditMemo->getGwCardBasePrice() +
            $creditMemo->getBaseSubtotal();
        self::assertEquals($expectedTaxAmount, $creditMemo->getTaxAmount());
        self::assertEquals($expectedBaseTaxAmount, $creditMemo->getBaseTaxAmount());
        self::assertEquals($expectedGrandTotal, $creditMemo->getGrandTotal());
        self::assertEquals($expectedBaseGrandTotal, $creditMemo->getBaseGrandTotal());
    }
}
