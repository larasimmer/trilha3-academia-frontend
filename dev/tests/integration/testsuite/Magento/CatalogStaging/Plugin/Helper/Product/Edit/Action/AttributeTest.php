<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Plugin\Helper\Product\Edit\Action;

use PHPUnit\Framework\TestCase;

/**
 * Test for \Magento\CatalogStaging\Plugin\Helper\Product\Edit\Action\Attribute
 */
class AttributeTest extends TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $om;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->om = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
    }

    /**
     * Checks if excluded attributes contains special price date fields.
     *
     * @magentoAppArea adminhtml
     */
    public function testGetExcludedAttributes()
    {
        $attributes = $this->getProductAttributeHelperPlugin()->getExcludedAttributes();

        $this->assertContains('special_from_date', $attributes);
        $this->assertContains('special_to_date', $attributes);
    }

    /**
     * Gets product attribute helper plugin.
     *
     * @return \Magento\Catalog\Helper\Product\Edit\Action\Attribute
     */
    private function getProductAttributeHelperPlugin()
    {
        return $this->om->get(\Magento\Catalog\Helper\Product\Edit\Action\Attribute::class);
    }
}
