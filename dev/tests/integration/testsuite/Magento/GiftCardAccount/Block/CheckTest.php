<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardAccount\Block;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Pricing\Helper\Data;
use Magento\Framework\Registry;
use Magento\Framework\View\LayoutInterface;
use Magento\GiftCardAccount\Api\Data\GiftCardAccountInterfaceFactory;
use Magento\GiftCardAccount\Model\Spi\GiftCardAccountManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Helper\Xpath;
use PHPUnit\Framework\TestCase;

/**
 * Test for gift card account check block.
 *
 * @see \Magento\GiftCardAccount\Block\Check
 * @magentoAppArea frontend
 * @magentoDbIsolation enabled
 */
class CheckTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var Check */
    private $block;

    /** @var Registry */
    private $registry;

    /** @var GiftCardAccountInterfaceFactory */
    private $giftCardAccountFactory;

    /** @var GiftCardAccountManagerInterface */
    private $giftCardManager;

    /** @var Data */
    private $helper;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->block = $this->objectManager->get(LayoutInterface::class)->createBlock(Check::class)
            ->setTemplate('Magento_GiftCardAccount::check.phtml');
        $this->registry = $this->objectManager->get(Registry::class);
        $this->giftCardAccountFactory = $this->objectManager->get(GiftCardAccountInterfaceFactory::class);
        $this->giftCardManager = $this->objectManager->get(GiftCardAccountManagerInterface::class);
        $this->helper = $this->objectManager->get(Data::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        $this->registry->unregister('current_giftcardaccount');

        parent::tearDown();
    }

    /**
     * @magentoDataFixture Magento/GiftCardAccount/_files/giftcardaccount.php
     *
     * @return void
     */
    public function testCheckBlock(): void
    {
        $giftCardAccount = $this->giftCardManager->requestByCode('giftcardaccount_fixture');
        $this->registerGiftCardAccount($giftCardAccount);
        $html = $this->block->toHtml();
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(
                sprintf(
                    "//p[contains(text(), '%s')]/strong[contains(text(), '%s')]",
                    __('Gift Card:'),
                    $giftCardAccount->getCode()
                ),
                $html
            ),
            sprintf('%s with code %s wasn\'t found.', __('Gift Card'), $giftCardAccount->getCode())
        );
        $balance = $this->helper->currency($giftCardAccount->getBalance(), true, false);
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(
                sprintf(
                    "//p[contains(text(), '%s')]//span[contains(text(), '%s')]",
                    __('Current Balance:'),
                    $balance
                ),
                $html
            ),
            sprintf('%s wasn\'t found or not equals to %s.', __('Current Balance:'), $balance)
        );
        $expirationDate = $this->block->getExpirationDate();
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(
                sprintf(
                    "//p[contains(text(), '%s')]/strong[contains(text(), '%s')]",
                    __('Expires:'),
                    $expirationDate
                ),
                $html
            ),
            sprintf('%s wasn\'t found or not equals to %s.', __('Expires:'), $expirationDate)
        );
    }

    /**
     * @return void
     */
    public function testCheckBlockWithEmptyGiftCardAccount(): void
    {
        $giftCardAccount = $this->giftCardAccountFactory->create();
        $this->registerGiftCardAccount($giftCardAccount);
        $html = $this->block->toHtml();
        $this->assertStringContainsString((string)__('Please enter a valid gift card code.'), strip_tags($html));
    }

    /**
     * Register gift card account in registry.
     *
     * @param $giftCardAccount
     * @return void
     */
    private function registerGiftCardAccount($giftCardAccount): void
    {
        $this->registry->unregister('current_giftcardaccount');
        $this->registry->register('current_giftcardaccount', $giftCardAccount);
    }
}
