<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\GiftCardAccount\Api;

use Magento\Customer\Model\Session;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartManagementInterface;
use PHPUnit\Framework\TestCase;
use Magento\TestFramework\Helper\Bootstrap;

class GiftCardAccountManagementInterfaceTest extends TestCase
{
    /**
     * @var GiftCardAccountManagementInterface
     */
    private $management;

    /**
     * @var CartManagementInterface
     */
    private $cardManagement;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->management = $objectManager->get(GiftCardAccountManagementInterface::class);
        $this->cardManagement = $objectManager->get(CartManagementInterface::class);
        $this->customerSession = $objectManager->get(Session::class);
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        $this->customerSession->logout();
    }

    /**
     * @magentoDataFixture Magento/GiftCardAccount/_files/quote_with_giftcard_saved.php
     */
    public function testCheckGiftCard()
    {
        //Positive scenario.
        $cart = $this->cardManagement->getCartForCustomer(1);
        $balance = $this->management->checkGiftCard($cart->getId(), 'giftcardaccount_fixture');
        $this->assertEquals(9.99, $balance);

        //Invalid cart given.
        try {
            $this->management->checkGiftCard(99999, 'giftcardaccount_fixture');
            $this->fail('Invalid cart ID processed.');
        } catch (NoSuchEntityException $exception) {
            //Cart not found.
        }

        //Invalid code given.
        try {
            $this->management->checkGiftCard($cart->getId(), 'fake_code');
            $this->fail('Invalid gift card code processed.');
        } catch (NoSuchEntityException $exception) {
            $this->assertEquals(
                'The gift card code is either incorrect or expired. Verify and try again.',
                $exception->getMessage()
            );
        }
    }
}
