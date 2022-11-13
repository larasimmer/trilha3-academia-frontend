<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCard\Model\Product;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\GiftCard\Api\Data\GiftcardAmountInterface;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Gift card read handler test
 */
class ReadHandlerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var ReadHandler
     */
    private $readHandler;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->productRepository = $objectManager->get(ProductRepositoryInterface::class);
        $this->readHandler = $objectManager->get(ReadHandler::class);
    }

    /**
     * @magentoDataFixture Magento/GiftCard/_files/gift_card.php
     * @magentoDataFixture Magento/GiftCard/_files/gift_card_physical_with_fixed_amount_10.php
     * @magentoDataFixture Magento/GiftCard/_files/gift_card_with_amount.php
     */
    public function testExecute()
    {
        $giftCard = $this->productRepository->get(
            'gift-card',
            false,
            null,
            true
        );
        $readGiftCard = $this->readHandler->execute($giftCard);
        /** @var GiftcardAmountInterface[] $giftCardAmounts */
        $giftCardAmounts = $readGiftCard->getExtensionAttributes()->getGiftcardAmounts();
        $this->assertEmpty($giftCardAmounts);

        $giftCard10 = $this->productRepository->get(
            'gift-card-with-fixed-amount-10',
            false,
            null,
            true
        );
        $readGiftCard10 = $this->readHandler->execute($giftCard10);
        /** @var GiftcardAmountInterface[] $giftCard10Amounts */
        $giftCard10Amounts = $readGiftCard10->getExtensionAttributes()->getGiftcardAmounts();
        $this->assertEquals(10, $giftCard10Amounts[0]->getValue());

        $giftCardWithAmounts = $this->productRepository->get(
            'gift-card-with-amount',
            false,
            null,
            true
        );
        $readGiftCardWithAmounts = $this->readHandler->execute($giftCardWithAmounts);
        /** @var GiftcardAmountInterface[] $giftCardWithAmountsAmounts */
        $giftCardWithAmountsAmounts = $readGiftCardWithAmounts->getExtensionAttributes()->getGiftcardAmounts();
        $this->assertEquals(7, $giftCardWithAmountsAmounts[0]->getValue());
        $this->assertEquals(17, $giftCardWithAmountsAmounts[1]->getValue());
    }
}
