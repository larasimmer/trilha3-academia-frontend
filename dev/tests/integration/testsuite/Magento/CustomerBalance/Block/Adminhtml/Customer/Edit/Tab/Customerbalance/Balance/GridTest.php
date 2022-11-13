<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerBalance\Block\Adminhtml\Customer\Edit\Tab\Customerbalance\Balance;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\View\Result\Page;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Test for customer balance grid in admin.
 *
 * @see \Magento\CustomerBalance\Block\Adminhtml\Customer\Edit\Tab\Customerbalance\Balance\Grid
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class GridTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var Page */
    private $page;

    /** @var RequestInterface */
    private $request;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->page = $this->objectManager->get(PageFactory::class)->create();
        $this->request =  $this->objectManager->get(RequestInterface::class);
    }

    /**
     * @magentoDataFixture Magento/CustomerBalance/_files/customer_balance_default_website.php
     *
     * @return void
     */
    public function testBalanceGrid(): void
    {
        $this->request->setParam('id', 1);
        $this->preparePage();
        $block = $this->page->getLayout()->getBlock('customer.balance.grid');
        $this->assertNotFalse($block);
        $this->assertCount(1, $block->getPreparedCollection());
    }

    /**
     * Prepare page before render.
     *
     * @return void
     */
    private function preparePage(): void
    {
        $this->page->addHandle([
            'default',
            'adminhtml_customerbalance_form',
        ]);
        $this->page->getLayout()->generateXml();
    }
}
