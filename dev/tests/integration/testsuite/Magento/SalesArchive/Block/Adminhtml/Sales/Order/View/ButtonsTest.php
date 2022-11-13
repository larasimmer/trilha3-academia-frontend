<?php
/***
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesArchive\Block\Adminhtml\Sales\Order\View;

use Magento\Backend\Model\Search\AuthorizationMock;
use Magento\Framework\Authorization;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Helper\Xpath;
use PHPUnit\Framework\TestCase;

/**
 * Class to test order view page
 * @magentoAppArea adminhtml
 */
class ButtonsTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var Page */
    private $page;

    /** @var Registry */
    private $coreRegistry;

    /** @var OrderInterfaceFactory */
    private $orderFactory;

    /** @var PageFactory */
    private $pageFactory;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->pageFactory = $this->objectManager->get(PageFactory::class);
        $this->page = $this->pageFactory->create();
        $this->orderFactory = $this->objectManager->get(OrderInterfaceFactory::class);
        $this->coreRegistry = $this->objectManager->get(Registry::class);
        $this->objectManager->addSharedInstance(
            $this->objectManager->get(AuthorizationMock::class),
            Authorization::class
        );
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        $this->coreRegistry->unregister('sales_order');

        parent::tearDown();
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/SalesArchive/_files/archived_order_with_invoice_shipment_creditmemo.php
     * @magentoConfigFixture default_store sales/magento_salesarchive/active enabled
     * @return void
     */
    public function testArchiveButtonsAvailableOnOrderPageWithStatusComplete(): void
    {
        $this->registerOrderByIncrementId('100000111');
        $this->preparePageLayout();
        $html = $this->page->getLayout()
            ->getBlock('page.actions.toolbar')
            ->toHtml();
        $this->checkButtonsAvailableOnPage(
            $html,
            [
                'Credit Memo',
                'Send Email',
                'Reorder',
                'Move to Order Management',
            ]
        );
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/SalesArchive/_files/archived_pending_order.php
     * @magentoConfigFixture default_store sales/magento_salesarchive/active enabled
     * @return void
     */
    public function testArchiveButtonsAvailableOnOrderPageWithStatusPending(): void
    {
        $this->registerOrderByIncrementId('100000001');
        $this->preparePageLayout();
        $html = $this->page->getLayout()
            ->getBlock('page.actions.toolbar')
            ->toHtml();
        $this->checkButtonsAvailableOnPage(
            $html,
            [
                'Send Email',
                'Reorder',
                'Move to Order Management',
                'Ship',
                'Invoice',
                'Hold',
                'Edit',
                'Cancel',
            ]
        );
    }

    /**
     * Prepare page layout
     *
     * @return void
     */
    private function preparePageLayout(): void
    {
        $this->page->addHandle(['default', 'sales_order_view']);
        $this->page->getLayout()->generateXml();
    }

    /**
     * Check if buttons available on page
     *
     * @param string $html
     * @param array $buttons
     * @return void
     */
    private function checkButtonsAvailableOnPage(string $html, array $buttons): void
    {
        foreach ($buttons as $buttonTitle) {
            $title = (string)__($buttonTitle);
            $xPath = "//button[contains(@class, 'action-default')]/span[normalize-space(text())='$title']";
            $this->assertEquals(
                1,
                Xpath::getElementsCountForXpath($xPath, $html),
                "Button '$title' not found."
            );
        }
    }

    /**
     * Register order by increment
     *
     * @param string $incrementId
     * @return void
     */
    private function registerOrderByIncrementId(string $incrementId): void
    {
        $order = $this->orderFactory->create()->loadByIncrementId($incrementId);
        $this->coreRegistry->unregister('sales_order');
        $this->coreRegistry->register('sales_order', $order);
        $this->coreRegistry->unregister('current_order');
        $this->coreRegistry->register('current_order', $order);
    }
}
