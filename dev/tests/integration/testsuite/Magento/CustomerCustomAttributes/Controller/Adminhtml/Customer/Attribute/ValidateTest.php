<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Controller\Adminhtml\Customer\Attribute;

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

        $this->dispatch('backend/admin/customer_attribute/validate');

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

    /**
     * Tests that controller validate unique option values for attribute.
     *
     * @return void
     */
    public function testUniqueOption()
    {
        $params = $this->getRequestNewAttributeDataWithNotUniqueOptions();
        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setPostValue($params);

        $this->dispatch('backend/admin/customer_attribute/validate');

        $this->assertEquals(
            '{"error":true,"message":"The value of Admin must be unique."}',
            $this->getResponse()->getBody()
        );
    }

    /**
     * @return array
     */
    private function getRequestNewAttributeDataWithNotUniqueOptions(): array
    {
        return [
            'attribute_code' => 'test_dropdown',
            'frontend_label' => ['test_dropdown'],
            'frontend_input' => 'select',
            //@codingStandardsIgnoreStart
            'serialized_options' => '["option%5Border%5D%5Boption_0%5D=1&option%5Bvalue%5D%5Boption_0%5D%5B0%5D=1&option%5Bvalue%5D%5Boption_0%5D%5B1%5D=1&option%5Bdelete%5D%5Boption_0%5D=","option%5Border%5D%5Boption_1%5D=2&option%5Bvalue%5D%5Boption_1%5D%5B0%5D=1&option%5Bvalue%5D%5Boption_1%5D%5B1%5D=1&option%5Bdelete%5D%5Boption_1%5D="]',
            //@codingStandardsIgnoreEnd
            'sort_order' => 1,
        ];
    }

    /**
     * Tests that controller validate empty option values for attribute.
     *
     * @return void
     */
    public function testEmptyOption()
    {
        $params = $this->getRequestNewAttributeDataWithEmptyOption();
        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setPostValue($params);

        $this->dispatch('backend/admin/customer_attribute/validate');

        $this->assertEquals(
            '{"error":true,"message":"The value of Admin scope can\'t be empty."}',
            $this->getResponse()->getBody()
        );
    }

    /**
     * @return array
     */
    private function getRequestNewAttributeDataWithEmptyOption(): array
    {
        return [
            'attribute_code' => 'test_dropdown',
            'frontend_label' => ['test_dropdown'],
            'frontend_input' => 'select',
            //@codingStandardsIgnoreStart
            'serialized_options' => '["option%5Border%5D%5Boption_0%5D=1&option%5Bvalue%5D%5Boption_0%5D%5B0%5D=&option%5Bvalue%5D%5Boption_0%5D%5B1%5D=&option%5Bdelete%5D%5Boption_0%5D="]',
            //@codingStandardsIgnoreEnd
            'sort_order' => 1,
        ];
    }
}
