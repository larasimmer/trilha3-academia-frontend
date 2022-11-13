<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Controller\Cart;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Quote\Model\Quote;
use Magento\TestFramework\TestCase\AbstractBackendController;
use Magento\TestFramework\Quote\Model\GetQuoteByReservedOrderId;

/**
 * 'Reward Points' state controller integration tests.
 */
class ChangeTest extends AbstractBackendController
{
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var GetQuoteByReservedOrderId
     */
    private $getQuoteByReservedOrderId;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->checkoutSession = $this->_objectManager->get(CheckoutSession::class);
        $this->customerSession = $this->_objectManager->get(CustomerSession::class);
        $this->getQuoteByReservedOrderId = $this->_objectManager->get(GetQuoteByReservedOrderId::class);
    }

    /**
     * Check that Quote totals are correct after enabling 'Use Reward Points' mode.
     *
     * @return void
     * @magentoConfigFixture current_store magento_reward/general/is_enabled 1
     * @magentoDataFixture Magento/Reward/_files/reward_exchange_rates_one_to_one.php
     * @magentoDataFixture Magento/Reward/_files/customer_with_five_reward_points.php
     * @magentoDataFixture Magento/Sales/_files/quote_with_customer.php
     */
    public function testEnableUseRewardPointsMode(): void
    {
        $quote = $this->dispatchRequest('test01', 1, true);

        $this->assertEquals(true, $quote->getUseRewardPoints());
        $this->assertEquals(5., $quote->getRewardPointsBalance());
        $this->assertEquals(5., $quote->getGrandTotal());
    }

    /**
     * Check that Quote totals are correct after disabling 'Use Reward Points' mode.
     *
     * @return void
     * @magentoConfigFixture current_store magento_reward/general/is_enabled 1
     * @magentoDataFixture Magento/Reward/_files/customer_quote_with_reward_points.php
     */
    public function testDisableUseRewardPointsMode(): void
    {
        $quote = $this->dispatchRequest('55555555', 1, false);

        $this->assertEquals(false, $quote->getUseRewardPoints());
        $this->assertEquals(0, $quote->getRewardPointsBalance());
        $this->assertEquals(15., $quote->getGrandTotal());
    }

    /**
     * Check that user is redirected to 'Customer Account' page if 'Reward Points' is disabled.
     *
     * @return void
     * @magentoConfigFixture current_store magento_reward/general/is_enabled 0
     * @magentoDataFixture Magento/Reward/_files/customer_quote_with_reward_points.php
     */
    public function testDispatchWithDisabledFunctionality(): void
    {
        $quote = $this->dispatchRequest('55555555', 1, false);

        $this->assertStringContainsString(
            'customer/account/',
            $this->getResponse()->getHeader('Location')->getFieldValue()
        );
        $this->assertEquals(true, $quote->getUseRewardPoints());
    }

    /**
     * Check that guest user is redirected to 'Customer Login' page when trying to make request.
     *
     * @return void
     * @magentoConfigFixture current_store magento_reward/general/is_enabled 1
     * @magentoDataFixture Magento/Reward/_files/customer_quote_with_reward_points.php
     */
    public function testDispatchWithGuestUser(): void
    {
        $this->dispatchRequest('55555555', null, false);

        $this->assertStringContainsString(
            'customer/account/',
            $this->getResponse()->getHeader('Location')->getFieldValue()
        );
        $quote = $this->getQuoteByReservedOrderId->execute('55555555');
        $this->assertEquals(true, $quote->getUseRewardPoints());
    }

    /**
     * Prepare and dispatch request using provided data. Return Quote from session.
     *
     * @param string $reservedId
     * @param int|null $customerId
     * @param bool $useBalance
     * @return Quote
     */
    private function dispatchRequest(string $reservedId, ?int $customerId, bool $useBalance): Quote
    {
        $quoteId = $this->getQuoteByReservedOrderId->execute($reservedId)->getId();
        $this->checkoutSession->setQuoteId($quoteId);
        $this->customerSession->setCustomerId($customerId);

        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue(['useBalance' => $useBalance]);
        $this->dispatch('reward/cart/change');

        return $this->checkoutSession->getQuote();
    }
}
