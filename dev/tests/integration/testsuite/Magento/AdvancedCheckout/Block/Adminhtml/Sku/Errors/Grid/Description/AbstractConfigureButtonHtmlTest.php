<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Block\Adminhtml\Sku\Errors\Grid\Description;

use Magento\AdvancedCheckout\Block\Adminhtml\Sku\Errors\Grid\Description;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Enterprise\Model\ProductMetadata;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Class consist of base logic to check configure button in manage shopping cart page
 */
abstract class AbstractConfigureButtonHtmlTest extends TestCase
{
    /** @var ObjectManagerInterface */
    protected $objectManager;

    /** @var Description */
    protected $block;

    /** @var ProductRepositoryInterface */
    private $productRepository;

    /** @var DataObjectFactory */
    private $dataObjectFactory;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $productMetadataInterface = $this->objectManager->get(ProductMetadataInterface::class);
        if ($productMetadataInterface->getEdition() !== ProductMetadata::EDITION_NAME) {
            $this->markTestSkipped('Skipped, because this logic is rewritten on B2B.');
        }
        $this->block = $this->objectManager->get(LayoutInterface::class)->createBlock(Description::class);
        $this->dataObjectFactory = $this->objectManager->get(DataObjectFactory::class);
        $this->productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $this->productRepository->cleanCache();
    }

    /**
     * Prepare block
     *
     * @param string $productSku
     * @return void
     */
    protected function prepareBlock(string $productSku): void
    {
        $product = $this->productRepository->get($productSku);
        $item = $this->dataObjectFactory->create();
        $item->setData($product->getData());
        $this->block->setProduct($product);
        $this->block->setItem($item);
    }
}
