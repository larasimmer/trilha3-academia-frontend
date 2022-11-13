<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MultipleWishlist\Block\Search;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Helper\Xpath;
use PHPUnit\Framework\TestCase;

/**
 * Tests for search wish list form.
 *
 * @magentoDbIsolation enabled
 * @magentoAppArea frontend
 */
class FormTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var Form */
    private $block;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->block = $this->objectManager->get(LayoutInterface::class)->createBlock(Form::class)
            ->setTemplate('Magento_MultipleWishlist::search/form.phtml');
    }

    /**
     * @return void
     */
    public function testDisplaySearchWishListsByNameForm(): void
    {
        $elementsXpath = [
            'First Name input' => "//form[@id='wishlist-search-form']"
                . "//input[@name='params[firstname]']",
            'Last Name input' => "//form[@id='wishlist-search-form']"
                . "//input[@name='params[lastname]']",
            'Search button' => "//form[@id='wishlist-search-form']//button[contains(@class, 'search')]"
                . "/span[contains(text(), '" . __('Search') . "')]",
        ];
        $blockHtml = $this->block->toHtml();
        foreach ($elementsXpath as $element => $xpath) {
            $this->assertEquals(
                1,
                Xpath::getElementsCountForXpath($xpath, $blockHtml),
                sprintf('%s was not found.', $element)
            );
        }
    }

    /**
     * @return void
     */
    public function testDisplaySearchWishListsByEmailForm(): void
    {
        $elementsXpath = [
            'Owner Email input' => "//form[@id='wishlist-search-email-form']"
                . "//input[@name='params[email]']",
            'Search button' => "//form[@id='wishlist-search-email-form']"
                . "//button[contains(@class, 'search')]/span[contains(text(), '" . __('Search') . "')]",
        ];
        $blockHtml = $this->block->toHtml();
        foreach ($elementsXpath as $element => $xpath) {
            $this->assertEquals(
                1,
                Xpath::getElementsCountForXpath($xpath, $blockHtml),
                sprintf('%s was not found.', $element)
            );
        }
    }
}
