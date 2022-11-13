<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Block\Adminhtml\Sku\Errors\Grid;

use Magento\AdvancedCheckout\Helper\Data;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Indexer\TestCase;

/**
 * Checks description block appearance on manage shopping cart page
 *
 * @see \Magento\AdvancedCheckout\Block\Adminhtml\Sku\Errors\Grid\Description
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class DescriptionTest extends TestCase
{
    /** @var ObjectManagerInterface */
    protected $objectManager;

    /** @var Description */
    protected $block;

    /** @var DataObjectFactory */
    private $dataObjectFactory;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->block = $this->objectManager->get(LayoutInterface::class)->createBlock(Description::class);
        $this->dataObjectFactory = $this->objectManager->get(DataObjectFactory::class);
    }

    /**
     * @return void
     */
    public function testGetErrorMessage(): void
    {
        $item = $this->dataObjectFactory->create(['data' => ['code' => Data::ADD_ITEM_STATUS_FAILED_SKU]]);
        $this->assertEquals(
            (string)__('The SKU was not found in the catalog.'),
            (string)$this->block->getErrorMessage($item)
        );
    }
}
