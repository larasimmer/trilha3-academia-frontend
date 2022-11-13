<?php
/**<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Controller\Adminhtml\Rma\Item\Attribute;

use Magento\TestFramework\TestCase\AbstractBackendController;
use Magento\Framework\Data\Form\FormKey;

/**
 * @magentoAppArea adminhtml
 */
class ValidateTest extends AbstractBackendController
{
    /**
     * Tests that controller validate file extensions.
     *
     * @return void
     */
    public function testFileExtensions(): void
    {
        $params = $this->getRequestNewAttributeData();
        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setPostValue($params);

        $this->dispatch('backend/admin/rma_item_attribute/validate');

        $this->assertEquals(
            '{"error":true,"message":"Please correct the value for file extensions."}',
            $this->getResponse()->getBody()
        );
    }

    /**
     * Gets request params.
     *
     * @return array
     */
    private function getRequestNewAttributeData(): array
    {
        return [
            'attribute_code' => 'new_file',
            'frontend_label' => ['new_file'],
            'frontend_input' => 'file',
            'file_extensions' => 'php',
            'sort_order' => 1,
            'form_key' => $this->_objectManager->get(FormKey::class)->getFormKey(),
        ];
    }
}
