<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Controller\Adminhtml\Customer\Attribute;

use Magento\Customer\Model\Attribute;
use Magento\Customer\Model\AttributeFactory;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\FormFactory;
use Magento\Customer\Model\ResourceModel\Attribute as AttributeResource;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Escaper;
use Magento\Framework\Message\MessageInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Stdlib\DateTime;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * Test for save customer attribute controller.
 *
 * @see \Magento\CustomerCustomAttributes\Controller\Adminhtml\Customer\Attribute\Save
 * @magentoAppArea adminhtml
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SaveTest extends AbstractBackendController
{
    /** @var AttributeResource */
    private $attributeResource;

    /** @var FormFactory */
    private $formFactory;

    /** @var AttributeFactory */
    private $attributeFactory;

    /** @var string */
    private $attributeToDelete;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->attributeResource = $this->_objectManager->get(AttributeResource::class);
        $this->formFactory = $this->_objectManager->get(FormFactory::class);
        $this->attributeFactory = $this->_objectManager->get(AttributeFactory::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        if ($this->attributeToDelete) {
            $attribute = $this->attributeFactory->create()
                ->loadByCode(Customer::ENTITY, $this->attributeToDelete);
            if ($attribute->getId()) {
                $this->attributeResource->delete($attribute);
            }
        }

        parent::tearDown();
    }

    /**
     * @dataProvider wrongValidationDataProvider
     *
     * @param array $params
     * @param string $message
     * @return void
     */
    public function testSaveAttributeWithWrongData(array $params, string $message): void
    {
        $this->dispatchSaveCustomerAttributeRequest($params);
        $this->assertSessionMessages($this->containsEqual($message), MessageInterface::TYPE_ERROR);
        $this->assertRedirect($this->stringContains('backend/admin/customer_attribute/edit'));
    }

    /**
     * @return array
     */
    public function wrongValidationDataProvider(): array
    {
        $dateTime = new \DateTimeImmutable();
        return [
            'Incorrect date validation' => [
                'params' => [
                    'attribute_code' => 'test_attribute',
                    'frontend_label' => ['test_attribute'],
                    'frontend_input' => 'date',
                    'date_range_min' => $dateTime->format('m/d/Y'),
                    'date_range_max' => $dateTime->modify('-1 day')->format('m/d/Y'),
                ],
                'message' => (string)__('Please correct the values for minimum and maximum date validation rules.'),
            ],
            'Incorrect length validation' => [
                'params' => [
                    'attribute_code' => 'test_attribute',
                    'frontend_label' => ['test_attribute'],
                    'frontend_input' => 'text',
                    'input_validation' => 'length',
                    'min_text_length' => 50,
                    'max_text_length' => 49,
                ],
                'message' => (string)__(
                    'Please correct the values for minimum and maximum text length validation rules.'
                ),
            ],
            'Incorrect validation for file extensions' => [
                'params' => [
                    'attribute_code' => 'test_attribute',
                    'frontend_label' => ['test_attribute'],
                    'frontend_input' => 'file',
                    'file_extensions' => 'php',
                    'sort_order' => 1,
                ],
                'message' => (string)__('Please correct the value for file extensions.'),
            ],
            'Option with empty admin value' => [
                'params' => [
                    'attribute_code' => 'test_dropdown',
                    'frontend_label' => ['test_dropdown'],
                    'frontend_input' => 'select',
                    'serialized_options' => $this->serializeOptions(
                        [
                            [
                                'option' => [
                                    'order' => ['option_0' => '1'],
                                    'value' => ['option_0' => ['', '']],
                                    'delete' => ['option_0' => ''],
                                ],
                            ],
                        ]
                    ),
                    'sort_order' => 1,
                ],
                'message' => (string)__('The value of Admin scope can\'t be empty.'),
            ],
            'Option with not unique value' => [
                'params' => [
                    'attribute_code' => 'test_dropdown',
                    'frontend_label' => ['test_dropdown'],
                    'frontend_input' => 'select',
                    'serialized_options' => $this->serializeOptions(
                        [
                            [
                                'option' => [
                                    'order' => ['option_0' => '1'],
                                    'value' => ['option_0' => ['1', '1']],
                                    'delete' => ['option_0' => ''],
                                ],
                            ],
                            [
                                'option' => [
                                    'order' => ['option_1' => '2'],
                                    'value' => ['option_1' => ['1', '1']],
                                    'delete' => ['option_1' => ''],
                                ],
                            ],
                        ]
                    ),
                    'sort_order' => 1,
                ],
                'message' => (string)__('The value of Admin must be unique.'),
            ],
            'Option with invalid serialized options' => [
                'params' => [
                    'attribute_code' => 'test_dropdown',
                    'frontend_label' => ['test_dropdown'],
                    'frontend_input' => 'select',
                    'serialized_options' => '',
                    'sort_order' => 1,
                ],
                'message' => Bootstrap::getObjectManager()->get(Escaper::class)->escapeHtml(
                    (string)__('The attribute couldn\'t be saved due to an error. '
                    . 'Verify your information and try again. If the error persists, please try again later.')
                ),
            ],
        ];
    }

    /**
     * @dataProvider wrongParamsDataProvider
     *
     * @param array $params
     * @param string $message
     * @return void
     */
    public function testSaveAttributeWrongFilteringData(array $params, string $message): void
    {
        $this->dispatchSaveCustomerAttributeRequest($params);
        $this->assertSessionMessages($this->containsEqual($message), MessageInterface::TYPE_ERROR);
        $this->assertRedirect($this->stringContains('backend/admin/customer_attribute/new'));
    }

    /**
     * @return array
     */
    public function wrongParamsDataProvider(): array
    {
        return [
            'Wrong attribute code' => [
                'params' => [
                    'attribute_code' => 'test-dropdown',
                    'frontend_label' => ['test_dropdown'],
                    'frontend_input' => 'select',
                ],
                'message' => (string)__('The attribute code is invalid. Please use only letters (a-z), '
                . 'numbers (0-9) or underscores (_) in this field. The first character should be a letter.'),
            ],
            'Without required forms to show on storefront' => [
                'params' => [
                    'attribute_code' => 'test_dropdown',
                    'frontend_label' => ['test_dropdown'],
                    'frontend_input' => 'select',
                    'is_required' => 1,
                    'is_visible' => 1,
                    'used_in_forms' => [],
                ],
                'message' => (string)__(
                    'No forms to use in specified to show attribute on a storefront. Please select one at least.'
                ),
            ],
            'With wrong frontend input' => [
                'params' => [
                    'attribute_code' => 'test_dropdown',
                    'frontend_label' => ['test_dropdown'],
                    'frontend_input' => 'incorrect_type',
                ],
                'message' => (string)__('Input type "incorrect_type" not found in the input types list.'),
            ],
        ];
    }

    /**
     * @magentoDataFixture Magento/CustomerCustomAttributes/_files/address_multiselect_attribute.php
     *
     * @return void
     */
    public function testEditAnotherTypeAttribute(): void
    {
        $attribute = $this->attributeFactory->create()->loadByCode('customer_address', 'multi_select_attribute_code');
        $this->assertNotNull($attribute->getId());
        $params = ['frontend_label' => ['test_dropdown']];
        $this->dispatchSaveCustomerAttributeRequest($params, $attribute->getId());
        $this->assertSessionMessages(
            $this->containsEqual((string)__('You cannot edit this attribute.')),
            MessageInterface::TYPE_ERROR
        );
        $this->assertRedirect($this->stringContains('backend/admin/customer_attribute/index'));
    }

    /**
     * @return void
     */
    public function testEditAttributeWithBackendModelInParams(): void
    {
        $attribute = $this->attributeFactory->create()->loadByCode(Customer::ENTITY, 'dob');
        $this->assertNotNull($attribute->getId());
        $params = array_merge($attribute->getData(), ['frontend_label' => ['Date of Birth']]);
        $this->dispatchSaveCustomerAttributeRequest($params, $attribute->getId());
        $this->assertSessionMessages(
            $this->containsEqual((string)__('You cannot edit this attribute.')),
            MessageInterface::TYPE_ERROR
        );
        $this->assertRedirect($this->stringContains('backend/admin/customer_attribute/index'));
    }

    /**
     * @magentoDbIsolation disabled
     *
     * @return void
     */
    public function testSaveCustomerCustomAttributeTypeSelect(): void
    {
        $attributeOptions = [
            [
                'option' => [
                    'order' => ['option_0' => '1'],
                    'value' => ['option_0' => ['Option 1', 'Option 1']],
                    'delete' => ['option_0' => ''],
                ],
            ],
            [
                'option' => [
                    'order' => ['option_1' => '2'],
                    'value' => ['option_1' => ['Option 2', 'Option 2']],
                    'delete' => ['option_1' => ''],
                ],
            ],
        ];
        $params = [
            'attribute_code' => 'test_dropdown',
            'frontend_label' => ['test_dropdown'],
            'frontend_input' => 'select',
            'serialized_options' => $this->serializeOptions($attributeOptions),
            'sort_order' => 1,
            'is_required' => 1,
            'is_visible' => 1,
            'used_in_forms' => [
                'customer_account_create',
                'customer_account_edit',
                'adminhtml_checkout',
            ],
        ];
        $this->dispatchSaveCustomerAttributeRequest($params);
        $this->attributeToDelete = $params['attribute_code'];
        $this->assertSessionMessages(
            $this->containsEqual((string)__('You saved the customer attribute.')),
            MessageInterface::TYPE_SUCCESS
        );
        $this->assertRedirect($this->stringContains('backend/admin/customer_attribute/index'));
        $attribute = $this->attributeFactory->create()
            ->loadByCode(Customer::ENTITY, $params['attribute_code']);
        $this->assertNotNull($attribute);
        $this->assertAttributeOptions($attribute, $attributeOptions);
        unset($params['serialized_options']);
        $this->assertAttributeData($attribute, $params);
    }

    /**
     * @magentoDbIsolation disabled
     *
     * @magentoDataFixture Magento/CustomerCustomAttributes/_files/customer_attribute_type_select.php
     *
     * @return void
     */
    public function testEditCustomerCustomAttributeTypeSelect(): void
    {
        $attribute = $this->attributeFactory->create()
            ->loadByCode(Customer::ENTITY, 'customer_attribute_type_select');
        $this->assertNotNull($attribute->getId());
        $attributeOptions = [
            [
                'option' => [
                    'order' => ['option_0' => '1'],
                    'value' => ['option_0' => ['Updated Option 1', 'Updated Option 1']],
                    'delete' => ['option_0' => ''],
                ],
            ],
            [
                'option' => [
                    'order' => ['option_1' => '2'],
                    'value' => ['option_1' => ['Updated Option 2', 'Updated Option 2']],
                    'delete' => ['option_1' => ''],
                ],
            ],
        ];
        $params = [
            'frontend_label' => ['updated_test_dropdown'],
            'serialized_options' => $this->serializeOptions($attributeOptions),
            'sort_order' => 56,
            'is_required' => 1,
            'is_visible' => 1,
            'used_in_forms' => ['customer_account_edit'],
        ];
        $this->dispatchSaveCustomerAttributeRequest($params, $attribute->getId());
        $this->assertSessionMessages(
            $this->containsEqual((string)__('You saved the customer attribute.')),
            MessageInterface::TYPE_SUCCESS
        );
        $this->assertRedirect($this->stringContains('backend/admin/customer_attribute/index'));
        $attribute = $this->attributeFactory->create()->loadByCode(Customer::ENTITY, $attribute->getAttributeCode());
        $this->assertNotNull($attribute->getId());
        $this->assertAttributeOptions($attribute, $attributeOptions);
        unset($params['serialized_options']);
        $this->assertAttributeData($attribute, $params);
    }

    /**
     * Dispatch save customer attribute request.
     *
     * @param array $postParams
     * @param string|null $attributeId
     * @return void
     */
    private function dispatchSaveCustomerAttributeRequest(array $postParams, ?string $attributeId = null): void
    {
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue($postParams)->setParam('attribute_id', $attributeId);
        $this->dispatch('backend/admin/customer_attribute/save');
    }

    /**
     * Create serialized options string.
     *
     * @param array $options
     * @return string
     */
    private function serializeOptions(array $options): string
    {
        $json = Bootstrap::getObjectManager()->get(SerializerInterface::class);
        $resultArray = [];
        foreach ($options as $option) {
            $resultArray[] = http_build_query($option);
        }

        return $json->serialize($resultArray);
    }

    /**
     * Assert attribute data.
     *
     * @param Attribute $attribute
     * @param array $expectedData
     * @return void
     */
    private function assertAttributeData(Attribute $attribute, array $expectedData): void
    {
        foreach ($expectedData as $valueKey => $value) {
            $value = $valueKey === 'frontend_label' ? $value = current($value) : $value;
            if ($valueKey === 'used_in_forms') {
                foreach ($value as $formCode) {
                    $this->assertNotFalse(
                        $this->formFactory->create()->setFormCode($formCode)
                            ->getAttribute($attribute->getAttributeCode()),
                        sprintf('Attribute wasn\'t found in "%s" form.', $formCode)
                    );
                }
                continue;
            }
            $this->assertEquals($value, $attribute->getData($valueKey));
        }
    }

    /**
     * Assert attribute options.
     *
     * @param Attribute $attribute
     * @param array $expectedOptions
     * @return void
     */
    private function assertAttributeOptions(Attribute $attribute, array $expectedOptions): void
    {
        foreach ($expectedOptions as $expectedOption) {
            $valueItemArr = $expectedOption['option']['value'];
            $optionLabel = reset($valueItemArr)[1];
            $optionFounded = false;
            foreach ($attribute->getOptions() as $attributeOption) {
                if ($attributeOption->getLabel() === $optionLabel) {
                    $optionFounded = true;
                    break;
                }
            }
            $this->assertTrue($optionFounded, sprintf('%s option wasn\'t found.', $optionLabel));
        }
    }
}
