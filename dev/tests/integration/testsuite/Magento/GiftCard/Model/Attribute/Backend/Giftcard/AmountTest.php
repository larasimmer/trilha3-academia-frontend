<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCard\Model\Attribute\Backend\Giftcard;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Framework\Exception\LocalizedException;
use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard;
use Magento\TestFramework\Helper\Bootstrap;

class AmountTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Bootstrap
     */
    private $objectManager;

    /**
     * @var Amount
     */
    private $amount;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();

        $this->amount = $this->objectManager->create(Amount::class);
        /** @var Attribute|\PHPUnit\Framework\MockObject\MockObject $attribute */
        $attribute = $this->getMockBuilder(Attribute::class)
            ->disableOriginalConstructor()
            ->getMock();
        $attribute->method('getAttributeCode')
            ->willReturn('giftcard_amounts');
        $attribute->method('getName')
            ->willReturn('giftcard_amounts');
        $this->amount->setAttribute($attribute);
    }

    /**
     * Tests validation exception if gift card amount is empty.
     *
     */
    public function testValidateEmptyAmount()
    {
        $amount = [];
        $allowOpenAmount = 0;

        $giftCardProduct = $this->createGiftCardProduct($amount, $allowOpenAmount);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Amount should be specified or Open Amount should be allowed');
        $this->amount->validate($giftCardProduct);
    }

    /**
     * Tests positive validation if gift card amount is not empty.
     *
     * @param array $amount
     * @param int $allowOpenAmount
     * @dataProvider validateNotEmptyAmountDataProvider
     */
    public function testValidateNotEmptyAmount(array $amount, int $allowOpenAmount)
    {
        $giftCardProduct = $this->createGiftCardProduct($amount, $allowOpenAmount);

        $this->assertSame(
            $this->amount,
            $this->amount->validate($giftCardProduct)
        );
    }

    /**
     * @return array
     */
    public function validateNotEmptyAmountDataProvider()
    {
        $filledAmount = [
            [
                'website_id' => '0',
                'website_value' => '300.00'
            ]
        ];
        $emptyAmount = [];

        return [
            [
                'amount' => $filledAmount,
                'allowOpenAmount' => 0
            ],
            [
                'amount' => $filledAmount,
                'allowOpenAmount' => 1
            ],
            [
                'amount' => $emptyAmount,
                'allowOpenAmount' => 1
            ],
        ];
    }

    /**
     * Tests validation exception if gift card amount is empty.
     */
    public function testValidateDuplicatedAmount()
    {
        $duplicatedAmounts = [
            [
                'website_id' => '1',
                'website_value' => '300.00'
            ],
            [
                'website_id' => '1',
                'website_value' => '300.00'
            ],
        ];
        $allowOpenAmount = 0;

        $giftCardProduct = $this->createGiftCardProduct($duplicatedAmounts, $allowOpenAmount);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Duplicate amount found.');
        $this->amount->validate($giftCardProduct);
    }

    /**
     * Returns gift card product with given amounts.
     *
     * @param array $amount
     * @param int $allowOpenAmount
     * @return mixed
     */
    private function createGiftCardProduct(array $amount, int $allowOpenAmount)
    {
        $product = $this->objectManager->create(Product::class);
        $product->setTypeId(Giftcard::TYPE_GIFTCARD)
            ->setName('Simple Gift Card')
            ->setSku('gift-card')
            ->setAllowOpenAmount($allowOpenAmount)
            ->setGiftcardAmounts($amount);
        ;

        return $product;
    }
}
