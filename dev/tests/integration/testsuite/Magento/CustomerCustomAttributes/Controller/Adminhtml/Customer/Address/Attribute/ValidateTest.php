<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Controller\Adminhtml\Customer\Address\Attribute;

use Magento\Framework\App\Request\Http;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * Checks validate action
 *
 * @see \Magento\CustomerCustomAttributes\Controller\Adminhtml\Customer\Address\Attribute\Validate
 *
 * @magentoAppArea adminhtml
 */
class ValidateTest extends AbstractBackendController
{
    /** @var SerializerInterface */
    private $json;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->json = $this->_objectManager->get(SerializerInterface::class);
    }

    /**
     * Tests that controller validate file extensions.
     *
     * @return void
     */
    public function testFileExtensions(): void
    {
        $this->dispatchWithPostParams([
            'attribute_code' => 'new_file',
            'frontend_label' => ['new_file'],
            'frontend_input' => 'file',
            'file_extensions' => 'php',
            'sort_order' => 1,
        ]);
        $this->assertErrorMessage('Please correct the value for file extensions.');
    }

    /**
     * Tests that controller validate unique option values for attribute.
     *
     * @return void
     */
    public function testUniqueOption(): void
    {
        $this->dispatchWithPostParams([
            'attribute_code' => 'test_dropdown',
            'frontend_label' => ['test_dropdown'],
            'frontend_input' => 'select',
            //@codingStandardsIgnoreStart
            'serialized_options' => '["option%5Border%5D%5Boption_0%5D=1&option%5Bvalue%5D%5Boption_0%5D%5B0%5D=1&option%5Bvalue%5D%5Boption_0%5D%5B1%5D=1&option%5Bdelete%5D%5Boption_0%5D=","option%5Border%5D%5Boption_1%5D=2&option%5Bvalue%5D%5Boption_1%5D%5B0%5D=1&option%5Bvalue%5D%5Boption_1%5D%5B1%5D=1&option%5Bdelete%5D%5Boption_1%5D="]',
            //@codingStandardsIgnoreEnd
            'sort_order' => 1,
        ]);
        $this->assertErrorMessage('The value of Admin must be unique.');
    }

    /**
     * Tests that controller validate empty option values for attribute.
     *
     * @return void
     */
    public function testEmptyOption(): void
    {
        $this->dispatchWithPostParams([
            'attribute_code' => 'test_dropdown',
            'frontend_label' => ['test_dropdown'],
            'frontend_input' => 'select',
            //@codingStandardsIgnoreStart
            'serialized_options' => '["option%5Border%5D%5Boption_0%5D=1&option%5Bvalue%5D%5Boption_0%5D%5B0%5D=&option%5Bvalue%5D%5Boption_0%5D%5B1%5D=&option%5Bdelete%5D%5Boption_0%5D="]',
            //@codingStandardsIgnoreEnd
            'sort_order' => 1,
        ]);
        $this->assertErrorMessage('The value of Admin scope can\'t be empty.');
    }

    /**
     * @return void
     */
    public function testSuccess(): void
    {
        $this->dispatchWithPostParams([
            'attribute_code' => 'test_dropdown',
            'frontend_label' => ['test_dropdown'],
            'frontend_input' => 'text',
            'sort_order' => 1,
        ]);
        $response = $this->json->unserialize($this->getResponse()->getBody());
        $this->assertFalse($response['error']);
    }

    /**
     * Dispatch request with params
     *
     * @param array $params
     * @return void
     */
    private function dispatchWithPostParams(array $params): void
    {
        $this->getRequest()->setMethod(Http::METHOD_POST)->setPostValue($params);
        $this->dispatch('backend/admin/customer_address_attribute/validate');
    }

    /**
     * Assert that response error message match expected value
     *
     * @param string $expectedMessage
     * @return void
     */
    private function assertErrorMessage(string $expectedMessage): void
    {
        $response = $this->json->unserialize($this->getResponse()->getBody());
        $this->assertNotEmpty($response);
        $this->assertTrue($response['error']);
        $this->assertEquals((string)__($expectedMessage), $response['message']);
    }
}
