<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Block\Adminhtml\Customer\Edit\Tab\Reward\History;

use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Checks reward points history grid in admin panel
 *
 * @see \Magento\Reward\Block\Adminhtml\Customer\Edit\Tab\Reward\History\Grid
 *
 * @magentoAppArea adminhtml
 */
class GridTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var LayoutInterface */
    private $layout;

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
        $this->layout = $this->objectManager->get(LayoutInterface::class);
        $this->page = $this->objectManager->get(PageFactory::class)->create();
        $this->request =  $this->objectManager->get(RequestInterface::class);
    }

    /**
     * @magentoDataFixture Magento/Reward/_files/history.php
     *
     * @return void
     */
    public function testHistoryGrid(): void
    {
        $this->request->setParam('id', 1);
        $this->preparePage();
        $block = $this->layout->getBlock('adminhtml.reward.history.customer.edit.tab.grid');
        $this->assertCount(1, $block->getPreparedCollection());
    }

    /**
     * Prepare page before render
     *
     * @return void
     */
    private function preparePage(): void
    {
        $this->page->addHandle([
            'default',
            'adminhtml_customer_reward_history',
            'adminhtml_customer_reward_history_block',
        ]);
        $this->page->getLayout()->generateXml();
    }
}
