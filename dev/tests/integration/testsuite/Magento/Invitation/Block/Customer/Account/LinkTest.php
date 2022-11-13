<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Invitation\Block\Customer\Account;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Checks invitations link displaying in account dashboard
 *
 * @magentoAppArea frontend
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
class LinkTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var Page
     */
    private $page;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->page = $this->objectManager->get(PageFactory::class)->create();
    }

    /**
     * @magentoConfigFixture current_store magento_invitation/general/enabled 1
     *
     * @return void
     */
    public function testLinkWithGeneralEnabledConfig(): void
    {
        $this->preparePage();
        $this->assertLinkExists();
    }

    /**
     * @magentoConfigFixture current_store magento_invitation/general/enabled 0
     *
     * @return void
     */
    public function testLinkWithGeneralDisabledConfig(): void
    {
        $this->preparePage();
        $this->assertLinkNotExists();
    }

    /**
     * @magentoConfigFixture current_store magento_invitation/general/enabled_on_front 1
     *
     * @return void
     */
    public function testLinkWithFrontEnabledConfig(): void
    {
        $this->preparePage();
        $this->assertLinkExists();
    }

    /**
     * @magentoConfigFixture current_store magento_invitation/general/enabled_on_front 0
     *
     * @return void
     */
    public function testLinkWithFrontDisabledConfig(): void
    {
        $this->preparePage();
        $this->assertLinkNotExists();
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
            'customer_account',
        ]);
        $this->page->getLayout()->generateXml();
    }

    /**
     * Asserts that link block exists and has proper data.
     *
     * @return void
     */
    private function assertLinkExists(): void
    {
        $block = $this->page->getLayout()->getBlock('customer-account-navigation-magento-invitation-link');
        $this->assertNotFalse($block);
        $html = $block->toHtml();
        $this->assertStringContainsString('invitation/', $html);
        $this->assertEquals(__('My Invitations'), strip_tags($html));
    }

    /**
     * Asserts that link block does not exists.
     *
     * @return void
     */
    private function assertLinkNotExists(): void
    {
        $block = $this->page->getLayout()->getBlock('customer-account-navigation-magento-invitation-link');
        $this->assertFalse($block);
    }
}
