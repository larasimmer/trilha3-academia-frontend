<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Invitation\Block\Adminhtml\Invitation\View\Tab;

/**
 * Invitation create form
 *
 * @magentoAppArea adminhtml
 */
class GeneralTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @magentoAppIsolation enabled
     */
    public function testPrepareFormForCustomerGroup()
    {
        /** @var $objectManager \Magento\TestFramework\ObjectManager */
        $objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $objectManager->get(
            \Magento\Framework\View\DesignInterface::class
        )->setArea(
            \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE
        )->setDefaultDesignTheme();

        $block = $objectManager->create(\Magento\Invitation\Block\Adminhtml\Invitation\View\Tab\General::class);

        $this->assertStringContainsString("General", $block->getCustomerGroupCode(1));
        $this->assertStringContainsString("Wholesale", $block->getCustomerGroupCode(2));
        $this->assertStringContainsString("Retailer", $block->getCustomerGroupCode(3));
    }
}
