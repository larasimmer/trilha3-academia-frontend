<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCard\Model\Product;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\GiftCard\Api\Data\GiftcardAmountInterface;
use Magento\GiftCard\Model\Giftcard\AmountRepository;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Gift card delete handler test
 */
class DeleteHandlerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var AmountRepository
     */
    private $amountRepository;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->productRepository = $objectManager->get(ProductRepositoryInterface::class);
        $this->amountRepository = $objectManager->get(AmountRepository::class);
    }

    /**
     * @magentoDataFixture Magento/GiftCard/_files/gift_card_physical_with_fixed_amount_10.php
     * @magentoDataFixture Magento/GiftCard/_files/gift_card_with_amount.php
     */
    public function testExecute()
    {
        /** @var \Magento\Framework\Registry $registry */
        $registry = Bootstrap::getObjectManager()->get(\Magento\Framework\Registry::class);

        $giftCard10 = $this->productRepository->get(
            'gift-card-with-fixed-amount-10',
            false,
            null,
            true
        );
        // Verify before delete
        /** @var GiftcardAmountInterface[] $giftCard10Amounts */
        $giftCard10Amounts = $giftCard10->getExtensionAttributes()->getGiftcardAmounts();
        $this->assertCount(1, $giftCard10Amounts);
        $giftCard10AmountsId = $giftCard10Amounts[0]->getData('value_id');
        $amount = $this->amountRepository->get($giftCard10AmountsId);
        $this->assertEquals(10, $amount->getValue());

        // Delete gift card
        $registry->unregister('isSecureArea');
        $registry->register('isSecureArea', true);
        $this->productRepository->delete($giftCard10);
        $amount2 = $this->amountRepository->get($giftCard10AmountsId);

        // Verify after delete
        $this->assertEmpty($amount2->getData());
        $this->assertEmpty($giftCard10->getGiftcardAmounts());
        $registry->unregister('isSecureArea');
        $registry->register('isSecureArea', false);

        $giftCardWithAmounts = $this->productRepository->get(
            'gift-card-with-amount',
            false,
            null,
            true
        );
        // Verify before delete
        /** @var GiftcardAmountInterface[] $giftCardWithAmountsAmounts */
        $giftCardWithAmountsAmounts = $giftCardWithAmounts->getExtensionAttributes()->getGiftcardAmounts();
        $this->assertCount(2, $giftCardWithAmountsAmounts);
        foreach ($giftCardWithAmountsAmounts as $giftCardWithAmountsAmount) {
            $giftCardWithAmountsAmountsIds[] = $giftCardWithAmountsAmount->getData('value_id');
        }
        $this->assertEquals(7, $this->amountRepository->get($giftCardWithAmountsAmountsIds[0])->getValue());
        $this->assertEquals(17, $this->amountRepository->get($giftCardWithAmountsAmountsIds[1])->getValue());

        // Delete gift card
        $registry->unregister('isSecureArea');
        $registry->register('isSecureArea', true);
        $this->productRepository->delete($giftCardWithAmounts);

        // Verify after delete
        $this->assertEmpty($this->amountRepository->get($giftCardWithAmountsAmountsIds[0])->getData());
        $this->assertEmpty($this->amountRepository->get($giftCardWithAmountsAmountsIds[1])->getData());
        $registry->unregister('isSecureArea');
        $registry->register('isSecureArea', false);
    }
}
