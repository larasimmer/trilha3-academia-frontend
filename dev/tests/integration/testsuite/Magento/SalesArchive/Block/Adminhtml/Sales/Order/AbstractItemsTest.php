<?php
/***
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesArchive\Block\Adminhtml\Sales\Order;

use Magento\Framework\DataObject;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\LayoutInterface;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\Sales\Block\Items\AbstractItems;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Abstract class to test items
 */
abstract class AbstractItemsTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var LayoutInterface */
    protected $layout;

    /** @var AbstractItems */
    protected $block;

    /** @var Registry */
    private $registry;

    /** @var OrderInterfaceFactory */
    protected $orderFactory;

    /** @var string */
    protected $key;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->layout = $this->objectManager->get(LayoutInterface::class);
        $this->registry = $this->objectManager->get(Registry::class);
        $this->orderFactory = $this->objectManager->get(OrderInterfaceFactory::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        $this->registry->unregister($this->key);

        parent::tearDown();
    }

    /**
     * Add Item to register
     *
     * @param DataObject $item
     * @return void
     */
    protected function registerItem(DataObject $item): void
    {
        $this->registry->unregister($this->key);
        $this->registry->register($this->key, $item);
    }
}
