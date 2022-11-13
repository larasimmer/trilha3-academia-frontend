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
 * Test customer balance history in account.
 *
 * @see \Magento\CustomerBalance\Block\Account\History
 *
 * @magentoAppArea frontend
 * @magentoDbIsolation enabled
 */
class HistoryTest extends TestCase
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
        $this->block = $this->objectManager->get(LayoutInterface::class)->createBlock(History::class)
            ->setTemplate('Magento_CustomerBalance::account/history.phtml');
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
    public function testCustomerBalanceHistory(): void
    {
        $this->customerSession->setCustomerId(1);
        $collection = $this->block->getEvents();
        $this->assertNotFalse($collection);
        $item = $collection->getFirstItem();
        $this->assertNotNull($item->getId());
        $blockHtml = $this->block->toHtml();
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(
                sprintf(
                    "//div[contains(@class, 'block-title')]/strong[contains(text(), '%s')]",
                    __('Balance History')
                ),
                $blockHtml
            ),
            sprintf('%s title wasn\'t found.', __('Balance History'))
        );
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(
                sprintf(
                    "//td[contains(@class, 'actions') and contains(text(), '%s')]",
                    $this->block->getActionLabel($item->getAction())
                ),
                $blockHtml
            ),
            'History action wasn\'t found.'
        );
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(
                sprintf(
                    "//td[contains(@class, 'change')]/span[contains(text(), '%s')]",
                    $this->helper->currency($item->getBalanceDelta(), true, false)
                ),
                $blockHtml
            ),
            'History balance change wasn\'t found.'
        );
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(
                sprintf(
                    "//td[contains(@class, 'balance')]/span[contains(text(), '%s')]",
                    $this->helper->currency($item->getBalanceAmount(), true, false)
                ),
                $blockHtml
            ),
            'History balance wasn\'t found.'
        );
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(
                sprintf(
                    "//td[contains(@class, 'date') and contains(text(), '%s')]",
                    $this->block->formatDate($item->getUpdatedAt(), \IntlDateFormatter::SHORT, true)
                ),
                $blockHtml
            ),
            'History date wasn\'t found.'
        );
    }

    /**
     * @magentoConfigFixture current_store customer/magento_customerbalance/show_history 0
     * @magentoDataFixture Magento/CustomerBalance/_files/customer_balance_default_website.php
     *
     * @return void
     */
    public function testCustomerBalanceHistoryDisabled(): void
    {
        $this->customerSession->setCustomerId(1);
        $this->assertEmpty($this->block->toHtml());
    }

    /**
     * @magentoConfigFixture current_store customer/magento_customerbalance/show_history 1
     * @magentoDataFixture Magento/Customer/_files/customer.php
     *
     * @return void
     */
    public function testCustomerWithoutBalanceHistory(): void
    {
        $this->customerSession->setCustomerId(1);
        $this->assertEmpty($this->block->toHtml());
    }
}
