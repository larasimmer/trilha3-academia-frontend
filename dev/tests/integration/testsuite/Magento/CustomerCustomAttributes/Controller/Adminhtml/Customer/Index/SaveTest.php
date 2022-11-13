<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Controller\Adminhtml\Customer\Index;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * Save Customer with Customer Attributes integration tests.
 *
 * @magentoAppArea adminhtml
 */
class SaveTest extends AbstractBackendController
{
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->customerRepository = $this->_objectManager->get(CustomerRepositoryInterface::class);
    }

    /**
     * Check that multiline attribute value is correct after saving Customer.
     *
     * @param array $attributeData
     * @param string $expectedResult
     * @return void
     * @magentoDataFixture Magento/CustomerCustomAttributes/_files/customer_custom_multiline_attribute.php
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @dataProvider updateCustomerWithMultilineAttributeDataProvider
     */
    public function testUpdateCustomerWithMultilineAttribute(array $attributeData, string $expectedResult): void
    {
        $customerId = 1;
        $attributeCode = 'multiline_attribute';
        $postData = [
            'customer' => [
                'entity_id' => $customerId,
                $attributeCode => $attributeData
            ]
        ];

        $this->getRequest()->setMethod(HttpRequest::METHOD_POST)->setPostValue($postData);
        $this->dispatch('backend/customer/index/save');

        $customer = $this->customerRepository->getById($customerId);
        $actualResult = $customer->getCustomAttribute($attributeCode)->getValue();

        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * DataProvider for the testUpdateCustomerWithMultilineAttribute().
     *
     * @return array
     */
    public function updateCustomerWithMultilineAttributeDataProvider(): array
    {
        return [
            'save_with_empty_lines' => [['', '', ''], ''],
            'save_second_line' => [['', 'orange', ''], 'orange'],
            'save_first_and_third_lines' => [['apple', '', 'banana'], "apple\n\nbanana"],
            'save_without_empty_lines' => [['apple', 'orange', 'banana'], "apple\norange\nbanana"],
        ];
    }
}
