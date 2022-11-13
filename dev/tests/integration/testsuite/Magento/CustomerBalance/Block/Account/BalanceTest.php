<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerBalance\Block\Account;

use Magento\Customer\Model\Session;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Pricing\Helper\Data;
use Magento\Framework\View\LayoutInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Helper\Xpath;
use PHPUnit\Framework\TestCase;

/**
 * Test customer balance in account.
 *
 * @see \Magento\CustomerBalance\Block\Account\Balance
 *
 * @magentoAppArea frontend
 * @magentoDbIsolation enabled
 */
class BalanceTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var Balance */
    private $block;

    /** @var Session */
    private $customerSession;

    /** @var Data */
    private $helper;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->block = $this->objectManager->get(LayoutInterface::class)->createBlock(Balance::class)
            ->setTemplate('Magento_CustomerBalance::account/balance.phtml');
        $this->customerSession = $this->objectManager->get(Session::class);
        $this->helper = $this->objectManager->get(Data::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        $this->customerSession->setCustomerId(null);

        parent::tearDown();
    }

    /**
     * @magentoDataFixture Magento/CustomerBalance/_files/customer_balance_default_website.php
     *
     * @return void
     */
    public function testCustomerBalance(): void
    {
        $this->customerSession->setCustomerId(1);
        $amount = $this->helper->currency($this->block->getBalance());
        $blockHtml = $this->block->toHtml();
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(
                sprintf("//div[contains(@class, 'block-title')]/strong[contains(text(), '%s')]", __('Balance')),
                $blockHtml
            ),
            sprintf('%s title wasn\'t found.', __('Balance'))
        );
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(
                sprintf(
                    "//span[contains(@class, 'balance-price-label') and contains(text(), '%s')]",
                    __('Your balance is:')
                ),
                $blockHtml
            ),
            sprintf('Text "%s" wasn\'t found.', __('Your balance is:'))
        );
        $this->assertStringContainsString($amount, $blockHtml);
    }
}
