<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardAccount\Block\Account;

use Magento\Customer\Model\Session;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Helper\Xpath;
use PHPUnit\Framework\TestCase;

/**
 * Test gift card redeem block in account.
 *
 * @see \Magento\GiftCardAccount\Block\Account\Redeem
 *
 * @magentoAppArea frontend
 * @magentoDbIsolation enabled
 */
class RedeemTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var Redeem */
    private $block;

    /** @var Session */
    private $customerSession;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->block = $this->objectManager->get(LayoutInterface::class)->createBlock(Redeem::class);
        $this->customerSession = $this->objectManager->get(Session::class);
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
    public function testRedeemGiftCardBlock(): void
    {
        $this->customerSession->setCustomerId(1);
        $blockHtml = $this->block->setTemplate('Magento_GiftCardAccount::account/redeem_link.phtml')->toHtml();
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(
                sprintf(
                    "//div[contains(@class, 'block-title')]/strong[contains(text(), '%s')]",
                    __('Redeem Gift Card')
                ),
                $blockHtml
            ),
            sprintf('%s title wasn\'t found.', __('Redeem Gift Card'))
        );
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(
                sprintf("//div[contains(@class, 'block-content')]/p[contains(text(), '%s')]", __('Have a gift card?')),
                $blockHtml
            ),
            sprintf('"%s" text wasn\'t found.', __('Have a gift card?'))
        );
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(
                sprintf("//a[contains(@href, '/giftcard/customer/') and contains(text(), '%s')]", __('Click here')),
                $blockHtml
            ),
            'Link for redeem gift card wasn\'t found.'
        );
    }

    /**
     * @magentoConfigFixture current_store customer/magento_customerbalance/is_enabled 0
     *
     * @return void
     */
    public function testRedeemGiftCardBlockDisabled(): void
    {
        $this->assertFalse($this->block->canRedeem());
    }

    /**
     * @magentoDataFixture Magento/CustomerBalance/_files/customer_balance_default_website.php
     *
     * @return void
     */
    public function testRedeemGiftCardForm(): void
    {
        $blockHtml = $this->block->setTemplate('Magento_GiftCardAccount::account/redeem.phtml')->toHtml();
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(
                sprintf("//form[@id='giftcard-form']//span[contains(text(), '%s')]", __('Enter gift card code')),
                $blockHtml
            ),
            sprintf('Label "%s" for input wasn\'t found.', __('Enter gift card code'))
        );
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(
                "//form[@id='giftcard-form']//input[contains(@name, 'giftcard_code')]",
                $blockHtml
            ),
            'Input for gift card code wasn\'t found.'
        );
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(
                sprintf(
                    "//form[@id='giftcard-form']//button[contains(@class, 'redeem')]/span[contains(text(), '%s')]",
                    __('Redeem Gift Card')
                ),
                $blockHtml
            ),
            sprintf('Button %s wasn\'t found.', __('Redeem Gift Card'))
        );
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(
                sprintf(
                    "//form[@id='giftcard-form']//button[contains(@class, 'check')]/span[contains(text(), '%s')]",
                    __('Check status and balance')
                ),
                $blockHtml
            ),
            sprintf('Button %s wasn\'t found.', __('Check status and balance'))
        );
    }
}
