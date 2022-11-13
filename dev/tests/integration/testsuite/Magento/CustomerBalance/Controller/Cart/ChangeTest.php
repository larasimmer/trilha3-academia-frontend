<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerBalance\Controller\Cart;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\TestFramework\TestCase\AbstractBackendController;
use Magento\TestFramework\Quote\Model\GetQuoteByReservedOrderId;
use Magento\Quote\Model\Quote;

/**
 * 'Store Credit' state controller integration tests.
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
     * Check that Quote totals are correct after enabling 'Use Customer Balance' mode.
     *
     * @return void
     * @magentoConfigFixture current_store customer/magento_customerbalance/is_enabled 1
     * @magentoDataFixture Magento/CustomerBalance/_files/customer_balance_default_website.php
     * @magentoDataFixture Magento/Sales/_files/quote_with_customer.php
     */
    public function testEnableUseCustomerBalanceMode(): void
    {
        $quote = $this->dispatchRequest('test01', 1, true);

        $this->assertEquals(true, $quote->getUseCustomerBalance());
        $this->assertEquals(10., $quote->getCustomerBalanceAmountUsed());
        $this->assertEquals(0., $quote->getGrandTotal());
    }

    /**
     * Check that Quote totals are correct after disabling 'Use Customer Balance' mode.
     *
     * @return void
     * @magentoConfigFixture current_store customer/magento_customerbalance/is_enabled 1
     * @magentoDataFixture Magento/CustomerBalance/_files/quote_with_customer_balance.php
     */
    public function testDisableUseCustomerBalanceMode(): void
    {
        $quote = $this->dispatchRequest('test01', 1, false);

        $this->assertEquals(false, $quote->getUseCustomerBalance());
        $this->assertEquals(0., $quote->getCustomerBalanceAmountUsed());
        $this->assertEquals(10., $quote->getGrandTotal());
    }

    /**
     * Check that user is redirected to 'Customer Account' page if 'Customer Balance' is disabled.
     *
     * @return void
     * @magentoConfigFixture current_store customer/magento_customerbalance/is_enabled 0
     * @magentoDataFixture Magento/CustomerBalance/_files/quote_with_customer_balance.php
     */
    public function testDispatchWithDisabledFunctionality(): void
    {
        $quote = $this->dispatchRequest('test01', 1, true);

        $this->assertStringContainsString(
            'customer/account/',
            $this->getResponse()->getHeader('Location')->getFieldValue()
        );
        $this->assertEquals(true, $quote->getUseCustomerBalance());
    }

    /**
     * Check that guest user is redirected to 'Customer Login' page when trying to make request.
     *
     * @return void
     * @magentoConfigFixture current_store customer/magento_customerbalance/is_enabled 1
     * @magentoDataFixture Magento/CustomerBalance/_files/quote_with_customer_balance.php
     */
    public function testDispatchWithGuestUser(): void
    {
        $this->dispatchRequest('test01', null, false);

        $this->assertStringContainsString(
            'customer/account/',
            $this->getResponse()->getHeader('Location')->getFieldValue()
        );
        $quote = $this->getQuoteByReservedOrderId->execute('test01');
        $this->assertEquals(true, $quote->getUseCustomerBalance());
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
        $this->dispatch('storecredit/cart/change');

        return $this->checkoutSession->getQuote();
    }
}
