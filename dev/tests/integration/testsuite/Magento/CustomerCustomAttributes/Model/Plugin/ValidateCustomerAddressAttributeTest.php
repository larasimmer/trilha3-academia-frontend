<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Model\Plugin;

use Magento\Customer\Model\Attribute;
use Magento\Customer\Model\AttributeFactory;
use Magento\Eav\Model\Config;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Interception\PluginList;
use PHPUnit\Framework\TestCase;

/**
 * Test for validate customer custom attribute plugin.
 *
 * @see \Magento\CustomerCustomAttributes\Model\Plugin\ValidateCustomerAddressAttribute
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class ValidateCustomerAddressAttributeTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var SerializerInterface */
    private $json;

    /** @var AttributeFactory */
    private $attributeFactory;

    /** @var int */
    private $entityTypeId;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->attributeFactory = $this->objectManager->get(AttributeFactory::class);
        $this->json = $this->objectManager->get(SerializerInterface::class);
        $this->entityTypeId = $this->objectManager->get(Config::class)->getEntityType('customer')->getId();
    }

    /**
     * @return void
     */
    public function testPluginIsRegistered(): void
    {
        $pluginInfo = $this->objectManager->get(PluginList::class)->get(Attribute::class);
        $this->assertSame(
            ValidateCustomerAddressAttribute::class,
            $pluginInfo['validateCustomerAddressAttribute']['instance']
        );
    }

    /**
     * @magentoDataFixture Magento/CustomerCustomAttributes/_files/customer_custom_attribute.php
     *
     * @return void
     */
    public function testValidateDuplicationAttributeCode(): void
    {
        $data = [
            'attribute_code' => 'multi_select_attribute_code',
            'backend_type' => 'varchar',
            'entity_type_id' => $this->entityTypeId,
        ];
        $attribute = $this->attributeFactory->create();
        $attribute->setData($data);
        $this->expectExceptionObject(new LocalizedException(__('An attribute with this code already exists.')));
        $attribute->beforeSave();
    }

    /**
     * @return void
     */
    public function testValidateCodeLengthAttribute(): void
    {
        $data = [
            'attribute_code' => 'very_long_multi_select_attribute_code_for_fail_validation',
            'backend_type' => 'varchar',
            'entity_type_id' => $this->entityTypeId,
        ];
        $attribute = $this->attributeFactory->create();
        $attribute->setData($data);
        $this->expectExceptionObject(new LocalizedException(
            __('The attribute code needs to be %1 characters or fewer. Re-enter the code and try again.', 51)
        ));
        $attribute->beforeSave();
    }

    /**
     * @dataProvider attributeOptionsDataProvider
     *
     * @param string $exceptionMessage
     * @param array|null $attributeOptions
     * @return void
     */
    public function testValidateAttributeWithOptions(string $exceptionMessage, ?array $attributeOptions = null): void
    {
        $data = [
            'attribute_code' => 'test_attribute',
            'frontend_input' => 'select',
            'entity_type_id' => $this->entityTypeId,
            'serialized_options' => $attributeOptions ? $this->serializeOptions($attributeOptions) : '',
        ];
        $attribute = $this->attributeFactory->create();
        $attribute->setData($data);
        $this->expectExceptionObject(new LocalizedException(__($exceptionMessage)));
        $attribute->beforeSave();
    }

    /**
     * @return array
     */
    public function attributeOptionsDataProvider(): array
    {
        return [
            'Wrong serialized options' => [
                'expected_message' => 'The attribute couldn\'t be validated due to an error.'
                    . ' Verify your information and try again. If the error persists, please try again later.',
            ],
            'Empty admin option' => [
                'expected_message' => 'The value of Admin scope can\'t be empty.',
                'serialized_options' => [
                    [
                        'option' => [
                            'order' => ['option_0' => '1'],
                            'value' => ['option_0' => ['', '']],
                            'delete' => ['option_0' => ''],
                        ],
                    ],
                ],
            ],
            'Options with not unique admin values' => [
                'expected_message' => 'The value of Admin must be unique.',
                'serialized_options' => [
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
                ],
            ],
        ];
    }

    /**
     * @return void
     */
    public function testValidateFileExtensionAttribute(): void
    {
        $data = [
            'attribute_code' => 'test_attribute',
            'frontend_input' => 'file',
            'entity_type_id' => $this->entityTypeId,
            'file_extensions' => 'php',
        ];
        $attribute = $this->attributeFactory->create();
        $attribute->setData($data);
        $this->expectExceptionObject(new LocalizedException(__('Please correct the value for file extensions.')));
        $attribute->beforeSave();
    }

    /**
     * Create serialized options string.
     *
     * @param array $optionsArr
     * @return string
     */
    private function serializeOptions(array $optionsArr): string
    {
        $resultArr = [];
        foreach ($optionsArr as $option) {
            $resultArr[] = http_build_query($option);
        }

        return $this->json->serialize($resultArr);
    }
}
