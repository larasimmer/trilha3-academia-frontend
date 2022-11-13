<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Controller\Adminhtml\Customer\Address\Attribute;

use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Model\Customer;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\ConfigFactory;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * Class check customer attribute save controller
 *
 * @see \Magento\CustomerCustomAttributes\Controller\Adminhtml\Customer\Address\Attribute\Save
 *
 * @magentoAppArea adminhtml
 */
class SaveTest extends AbstractBackendController
{
    private static $regionFrontendLabel = 'New region label';

    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    /** @var array */
    private $attributesToDelete = [];

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->attributeRepository = $this->_objectManager->get(AttributeRepositoryInterface::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        foreach ($this->attributesToDelete as $attributeCode) {
            try {
                $attribute = $this->attributeRepository->get('customer_address', $attributeCode);
                $this->attributeRepository->delete($attribute);
            } catch (NoSuchEntityException $e) {
                // attribute does not exist
            }
        }

        parent::tearDown();
    }

    /**
     * Tests that RegionId frontend label equal to Region frontend label.
     *
     * RegionId is hidden frontend input attribute and isn't available for updating via admin panel,
     * but frontend label of this attribute is visible in address forms as Region label.
     * So frontend label for RegionId should be synced with frontend label for Region attribute, which is
     * available for updating.
     *
     * @return void
     */
    public function testRegionFrontendLabelUpdate(): void
    {
        $regionAttribute = $this->attributeRepository->get(
            'customer_address',
            AddressInterface::REGION
        );
        $this->dispatchWithPostParams([
            'attribute_id' => $regionAttribute->getAttributeId(),
            'frontend_label' => [self::$regionFrontendLabel],
        ]);
        $this->assertSuccess();
        $regionIdAttribute = $this->attributeRepository->get('customer_address', AddressInterface::REGION_ID);

        self::assertEquals(self::$regionFrontendLabel, $regionIdAttribute->getDefaultFrontendLabel());
    }

    /**
     * Tests that controller validate file extensions.
     *
     * @dataProvider fileExtensionsDataProvider
     *
     * @param string $fileExtension
     * @return void
     */
    public function testFileExtensions(string $fileExtension): void
    {
        $this->dispatchWithPostParams([
            'attribute_code' => 'new_file',
            'frontend_label' => ['new_file'],
            'frontend_input' => 'file',
            'file_extensions' => $fileExtension,
            'sort_order' => 1,
        ]);

        $this->assertSessionMessages(
            $this->containsEqual((string)__('Please correct the value for file extensions.'))
        );
    }

    /**
     * @return array
     */
    public function fileExtensionsDataProvider(): array
    {
        return [
            ['php'],
            ['svg'],
            ['php3'],
            ['php4'],
            ['php5'],
            ['php7'],
            ['htaccess'],
            ['jsp'],
            ['pl'],
            ['py'],
            ['asp'],
            ['aspx'],
            ['sh'],
            ['cgi'],
            ['htm'],
            ['html'],
            ['phtml'],
            ['shtml'],
            ['phpt'],
            ['pht'],
            ['xml'],
        ];
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

        $this->assertSessionMessages(
            $this->containsEqual((string)__('The value of Admin must be unique.'))
        );
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

        $this->assertSessionMessages(
            $this->containsEqual((string)__('The value of Admin scope can\'t be empty.'))
        );
    }

    /**
     * Tests postcode input validation
     *
     * When postcode input validation set to null the associated attribute max_length
     * and min_length also become null
     *
     * @return void
     */
    public function testPostcodeInputValidation(): void
    {
        $postcodeAttribute = $this->attributeRepository->get(
            'customer_address',
            AddressInterface::POSTCODE
        );
        $this->dispatchWithPostParams([
            'attribute_id' => $postcodeAttribute->getAttributeId(),
            'attribute_code' => $postcodeAttribute->getAttributeCode(),
            'frontend_label' => ['Zip/Postal Code'],
            'frontend_input' => $postcodeAttribute->getFrontendInput(),
            'sort_order' => 110,
            'input_validation' => '',
            'min_text_length' => 4,
            'max_text_length' => 7,
        ]);
        $this->assertSuccess();
        $postcodeAttribute = $this->attributeRepository->get(
            'customer_address',
            AddressInterface::POSTCODE
        );

        self::assertEmpty($postcodeAttribute->getValidationRules());
    }

    /**
     * @return void
     */
    public function testSuccessfulSave(): void
    {
        $data = [
            'frontend_label' => [
                'Default Label',
                'Default Store View Label',
            ],
            'attribute_code' => 'attr_code_my',
            'frontend_input' => 'text',
            'is_required' => '1',
            'is_used_in_grid' => '1',
            'is_filterable_in_grid' => '1',
            'is_searchable_in_grid' => '1',
            'is_used_for_customer_segment' => '1',
            'is_visible' => '1',
            'sort_order' => '555',
            'used_in_forms' => [
                'customer_register_address',
                'customer_address_edit',
            ],
        ];

        $this->attributesToDelete[] = 'attr_code_my';
        $this->dispatchWithPostParams($data);
        $this->assertSuccess();
        $attribute = $this->attributeRepository->get('customer_address', 'attr_code_my');
        $this->assertAttributeData($attribute, $data);
    }

    /**
     * Assert that attribute data saved correctly
     *
     * @param AttributeInterface $attribute
     * @param array $expectedData
     * @return void
     */
    private function assertAttributeData(AttributeInterface $attribute, array $expectedData): void
    {
        $actualLabels = array_merge([$attribute->getDefaultFrontendLabel()], $attribute->getStoreLabels());
        $expectedData['is_user_defined'] = 1;
        $expectedData['is_system'] = 0;
        $this->assertEquals($expectedData['frontend_label'], $actualLabels);
        unset($expectedData['frontend_label']);
        foreach ($expectedData as $key => $value) {
            if ($key === 'used_in_forms') {
                $value[] = 'adminhtml_customer_address';
                $actualForms = $attribute->getUsedInForms();
                foreach ($value as $form) {
                    $this->assertContains($form, $actualForms);
                }
                continue;
            }
            $this->assertEquals($value, $attribute->getData($key));
        }
    }

    /**
     * @return void
     */
    public function testWithBackParam(): void
    {
        $this->dispatchWithPostParams(
            [
                'frontend_label' => ['Default Label'],
                'attribute_code' => 'valid_attr_code',
                'frontend_input' => 'text',
                'is_required' => 0,
                'is_visible' => 0,
                'used_in_form' => '',
            ],
            ['back' => true]
        );
        $this->assertSuccess(true);
    }

    /**
     * @magentoDbIsolation disabled
     *
     * @magentoDataFixture Magento/CustomerCustomAttributes/_files/customer_attribute_type_select.php
     *
     * @return void
     */
    public function testCannotUpdateAttribute(): void
    {
        $attribute = $this->attributeRepository->get(Customer::ENTITY, 'customer_attribute_type_select');
        $this->dispatchWithPostParams(
            ['frontend_label' => ['Default Label']],
            ['attribute_id' => $attribute->getId()]
        );
        $this->assertSessionMessages($this->containsEqual((string)__('You cannot edit this attribute.')));
        $this->assertRedirect($this->stringContains('backend/admin/customer_address_attribute/index/'));
    }

    /**
     * @magentoDataFixture Magento/CustomerCustomAttributes/_files/address_custom_attribute_without_transaction.php
     *
     * @return void
     */
    public function testUpdateAttribute(): void
    {
        $newDefaultValue = 'New Default Value';
        $attribute = $this->attributeRepository->get('customer_address', 'test_text_code');
        $this->dispatchWithPostParams(
            [
                'frontend_label' => ['Default Label'],
                'frontend_input' => 'text',
                'is_required' => 0,
                'is_visible' => 0,
                'used_in_form' => '',
                'default_value_text' => $newDefaultValue,
            ],
            ['attribute_id' => $attribute->getId()]
        );
        $this->assertSuccess();
        /** @var Config $eavConfig */
        $eavConfig = $this->_objectManager->get(ConfigFactory::class)->create();
        $updatedAttribute = $eavConfig->getAttribute('customer_address', 'test_text_code');
        $this->assertEquals($newDefaultValue, $updatedAttribute->getDefaultValue());
    }

    /**
     * Assert that controller successfully executed
     *
     * @param bool $backParam
     * @return void
     */
    private function assertSuccess(bool $backParam = false): void
    {
        $this->assertSessionMessages(
            $this->containsEqual((string)__('You saved the customer address attribute.'))
        );

        if ($backParam) {
            $this->assertRedirect(
                $this->stringContains('backend/admin/customer_address_attribute/edit/attribute_id/')
            );
        } else {
            $this->assertRedirect($this->stringContains('backend/admin/customer_address_attribute/index/'));
        }
    }

    /**
     * Dispatch request with params
     *
     * @param array $postParams
     * @param array $params
     * @return void
     */
    private function dispatchWithPostParams(array $postParams, array $params = []): void
    {
        $this->getRequest()->setParams($params);
        $this->getRequest()->setMethod(Http::METHOD_POST)->setPostValue($postParams);
        $this->dispatch('backend/admin/customer_address_attribute/save');
    }
}
