<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomAttributeManagement\Helper;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Class to check attribute management helper functionality
 *
 * @see \Magento\CustomAttributeManagement\Helper\Data
 *
 * @magentoAppArea adminhtml
 */
class DataTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var Data */
    private $helper;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->helper = $this->objectManager->get(Data::class);
    }

    /**
     * @dataProvider filterDataProvider
     *
     * @param array $data
     * @param array $expectedFilteredData
     * @return void
     */
    public function testFilterPostData(array $data, array $expectedFilteredData = []): void
    {
        $this->assertEquals(
            $this->hydrateData($expectedFilteredData),
            $this->helper->filterPostData($this->hydrateData($data))
        );
    }

    /**
     * @return array
     */
    public function filterDataProvider(): array
    {
        return [
            'frontend_label_with_html' => [
                'input_data' => [
                    'frontend_label' => [
                        "<h2>Label 1</h2>",
                        "<h2>Label 2</h2>",
                    ],
                ],
                'expected_output_data' => [
                    'frontend_label' => [
                        'Label 1',
                        'Label 2',
                    ],
                ],
            ],
            'empty_input_validation' => [
                'input_data' => [
                    'min_text_length' => '',
                    'max_text_length' => '',
                ],
            ],
        ];
    }

    /**
     * @return void
     */
    public function testFilterPostDataInvalidAttributeCode(): void
    {
        $expectedMessage = 'The attribute code is invalid. Please use only letters (a-z), '
            . 'numbers (0-9) or underscores (_) in this field. The first character should be a letter.';
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage((string)__($expectedMessage));
        $this->helper->filterPostData($this->hydrateData(['attribute_code' => '1 @ $']));
    }

    /**
     * @return void
     */
    public function testFilterPostDataUsedInFormFields(): void
    {
        $message = 'No forms to use in specified to show attribute on a storefront. Please select one at least.';
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage((string)__($message));
        $this->helper->filterPostData($this->hydrateData(['is_visible' => '1', 'is_required' => '1']));
    }

    /**
     * @return void
     */
    public function testGetAttributeValidateRules(): void
    {
        $data = [
            'date_range_min' => '07/18/2020',
            'date_range_max' => '07/31/2020',
        ];
        $expectedOutput = [
            'date_range_min' => 1595030400,
            'date_range_max' => 1596153600,
        ];
        $result = $this->helper->getAttributeValidateRules('date', $data);
        $this->assertEquals($expectedOutput, $result);
    }

    /**
     * @dataProvider validateRulesDataProvider
     *
     * @param string $frontendInput
     * @param array $rules
     * @param string $errorMessage
     * @return void
     */
    public function testCheckValidateRules(string $frontendInput, array $rules, string $errorMessage): void
    {
        $result = $this->helper->checkValidateRules($frontendInput, $rules);
        $this->assertNotEmpty($result);
        $this->assertEquals($errorMessage, (string)reset($result));
    }

    /**
     * @return array
     */
    public function validateRulesDataProvider(): array
    {
        return [
            'max_length_higher_then_min' => [
                'frontend_input' => 'text',
                'validate_rules' => [
                    'min_text_length' => '1000',
                    'max_text_length' => '1',
                ],
                'expected_message' => (string)
                __('Please correct the values for minimum and maximum text length validation rules.'),
            ],
            'max_date_range_higher_then_min' => [
                'frontend_input' => 'date',
                'validate_rules' => [
                    'date_range_min' => time(),
                    'date_range_max' => time() - 25000,
                ],
                'expected_message' => (string)
                __('Please correct the values for minimum and maximum date validation rules.'),
            ],
            'invalid_file_extension' => [
                'frontend_input' => 'file',
                'validate_rules' => [
                    'file_extensions' => 'jpeg,txt,php',
                ],
                'expected_message' => (string)__('Please correct the value for file extensions.'),
            ],
        ];
    }

    /**
     * Get static attribute data
     *
     * @return array
     */
    private function getStaticData(): array
    {
        return [
            'frontend_label' => [
                'Default Label',
            ],
            'attribute_code' => 'valid_attr_code',
            'frontend_input' => 'text',
            'is_required' => 0,
            'is_visible' => 0,
            'used_in_form' => '',
        ];
    }

    /**
     * Normalize attribute data
     *
     * @param array $inputData
     * @return array
     */
    private function hydrateData(array $inputData): array
    {
        return array_merge($this->getStaticData(), $inputData);
    }
}
