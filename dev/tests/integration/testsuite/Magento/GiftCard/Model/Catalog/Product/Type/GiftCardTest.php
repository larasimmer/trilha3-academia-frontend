<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCard\Model\Catalog\Product\Type;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\GiftCard\Api\Data\GiftcardAmountInterfaceFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GiftCardTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @magentoDataFixture Magento/GiftCard/_files/gift_card_physical_with_fixed_amount_10.php
     * @magentoDataFixture Magento/GiftCard/_files/gift_card_physical_with_fixed_amount_50.php
     * @magentoDataFixture Magento/GiftCard/_files/quote.php
     */
    public function testCollectTotalsWithPhysicalGiftCards()
    {
        $buyRequest = new DataObject(
            [
                'giftcard_sender_name' => 'test sender name',
                'giftcard_recipient_name' => 'test recipient name',
                'giftcard_message' => '',
                'qty' => 1,
            ]
        );
        /** @var Quote $quote */
        $quote = Bootstrap::getObjectManager()->create(Quote::class);
        $quote->load('test01', 'reserved_order_id');

        $productRepository = Bootstrap::getObjectManager()->create(
            ProductRepositoryInterface::class
        );
        $productOne = $productRepository->get('gift-card-with-fixed-amount-10', false, null, true);
        $productTwo = $productRepository->get('gift-card-with-fixed-amount-50', false, null, true);

        $quote->addProduct($productOne, $buyRequest);
        $quote->addProduct($productTwo, $buyRequest);

        $quote->collectTotals();

        $this->assertEquals(2, $quote->getItemsQty());
        $this->assertEquals(60, $quote->getGrandTotal());
        $this->assertEquals(60, $quote->getBaseGrandTotal());
    }

    /**
     * @magentoDataFixture Magento/GiftCard/_files/gift_card_physical_with_fixed_amount_50.php
     * @magentoDataFixture Magento/GiftCard/_files/quote.php
     */
    public function testFixedGiftCardAmountAddedToBuyRequest()
    {
        $buyRequest = new DataObject(
            [
                'giftcard_sender_name' => 'Sender Name',
                'giftcard_sender_email' => 'sender@example.com',
                'giftcard_recipient_name' => 'Recipient Name',
                'giftcard_recipient_email' => 'recipient@example.com',
                'giftcard_message' => 'Message',
                'qty' => 1,
            ]
        );
        /** @var Quote $quote */
        $quote = Bootstrap::getObjectManager()->create(Quote::class);
        $quote->load('test01', 'reserved_order_id');

        $productRepository = Bootstrap::getObjectManager()->create(
            ProductRepositoryInterface::class
        );
        $giftCardProduct = $productRepository->get('gift-card-with-fixed-amount-50', false, null, true);
        $quoteItem = $quote->addProduct($giftCardProduct, $buyRequest);
        $quoteItemBuyRequest = $quoteItem->getOptionByCode('info_buyRequest');
        $this->assertStringContainsString('"giftcard_amount":50', $quoteItemBuyRequest->getValue());
    }

    /**
     * @magentoDataFixture Magento/GiftCard/_files/gift_card_physical_with_fixed_amount_10.php
     * @magentoDataFixture Magento/GiftCard/_files/quote.php
     */
    public function testCollectTotalsAfterGiftCardsAmountChanged()
    {
        $amountData = [
            'value' => 20,
            'website_id' => 0,
            'attribute_id' => 132,
        ];
        $giftCardAmountFactory = Bootstrap::getObjectManager()->create(GiftcardAmountInterfaceFactory::class);
        $amount = $giftCardAmountFactory->create(['data' => $amountData]);
        $buyRequest = new DataObject(
            [
                'giftcard_sender_name' => 'test sender name',
                'giftcard_recipient_name' => 'test recipient name',
                'giftcard_message' => '',
                'qty' => 1,
            ]
        );
        /** @var Quote $quote */
        $quote = Bootstrap::getObjectManager()->create(Quote::class);
        $quote->load('test01', 'reserved_order_id');

        $productRepository = Bootstrap::getObjectManager()->create(
            ProductRepositoryInterface::class
        );
        $productOne = $productRepository->get('gift-card-with-fixed-amount-10', true);

        $quote->addProduct($productOne, $buyRequest);

        $productOne->setGiftcardAmounts([$amount]);
        $productRepository->save($productOne);
        $quote->collectTotals();

        $this->assertEquals(1, $quote->getItemsQty());
        $this->assertEquals(20, $quote->getGrandTotal());
        $this->assertEquals(20, $quote->getBaseGrandTotal());
    }
}
