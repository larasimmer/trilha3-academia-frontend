<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Block\Adminhtml\Sku\Errors\Grid\Description;

use Magento\Framework\Module\Manager;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Checks configure button appearance for composite product
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class ConfigureButtonHtmlTestCompositeProduct extends AbstractConfigureButtonHtmlTest
{
    /**
     * @inheritdoc
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $objectManager = Bootstrap::getObjectManager();
        /** @var Manager $moduleManager */
        $moduleManager = $objectManager->get(Manager::class);
        //This check is needed because module Magento_AdvancedCheckout is independent of Magento_Bundle
        if (!$moduleManager->isEnabled('Magento_Bundle')) {
            self::markTestSkipped('Magento_Bundle module disabled.');
        }
    }

    /**
     * Check button rendering for composite product
     *
     * @magentoDataFixture Magento/Bundle/_files/bundle_product_checkbox_options.php
     *
     * @return void
     */
    public function testGetConfigureButtonHtmlCompositeProduct(): void
    {
        $this->prepareBlock('bundle-product-checkbox-options');
        $result = $this->block->getConfigureButtonHtml();
        $this->assertStringNotContainsString('disabled="disabled', $result);
        $this->assertStringContainsString('addBySku.configure', $result);
        $this->assertStringContainsString((string)__('Configure'), strip_tags($result));
    }
}
