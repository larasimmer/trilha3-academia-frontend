<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardAccount\Model\Plugin;

use Magento\Quote\Api\CartManagementInterface;
use PHPUnit\Framework\TestCase;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Quote\Model\Quote\TotalsCollector;
use Magento\GiftCardAccount\Model\Giftcardaccount;
use Magento\GiftCardAccount\Api\GiftCardAccountRepositoryInterface;

/**
 * Tests that quote totals will be properly recalculated after giftcard account balance was changed
 */
class TotalsCollectorTest extends TestCase
{
    /**
     * @var CartManagementInterface
     */
    private $cartManagement;

    /**
     * @var TotalsCollector
     */
    private $totalsCollector;

    /**
     * @var GiftCardAccountRepositoryInterface
     */
    private $giftCardAccountRepository;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->cartManagement = $objectManager->get(CartManagementInterface::class);
        $this->totalsCollector = $objectManager->get(TotalsCollector::class);
        $this->giftCardAccountRepository = $objectManager->get(GiftCardAccountRepositoryInterface::class);
    }

    /**
     * @magentoDataFixture Magento/GiftCardAccount/_files/quote_with_giftcard_saved.php
     */
    public function testCheckGiftCard()
    {
        $cart = $this->cartManagement->getCartForCustomer(1);
        $model = $this->getGiftCardAccount('giftcardaccount_fixture');
        $this->assertEquals($cart->getBaseGiftCardsAmount(), 9.99);
        $this->assertEquals($cart->getGiftCardsAmount(), 9.99);
        $model->setBalance(5);
        $this->giftCardAccountRepository->save($model);
        $this->totalsCollector->collect($cart);
        $this->assertEquals($cart->getBaseGiftCardsAmount(), 5);
        $this->assertEquals($cart->getGiftCardsAmount(), 5);
    }

    /**
     * Get gift card account by code
     *
     * @param string $code
     * @return Giftcardaccount
     */
    private function getGiftCardAccount(string $code): Giftcardaccount
    {
        $objectManager = Bootstrap::getObjectManager();
        /** * @var Giftcardaccount $model */
        $model = $objectManager->create(Giftcardaccount::class);
        $model->loadByCode($code);

        return $model;
    }
}
