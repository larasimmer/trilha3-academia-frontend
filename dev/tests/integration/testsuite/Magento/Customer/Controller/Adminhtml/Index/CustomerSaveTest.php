<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Customer\Controller\Adminhtml\Index;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Data\Customer as CustomerData;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Message\MessageInterface;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * Tests for save customer via backend/customer/index/save controller.
 *
 * @magentoAppArea adminhtml
 */
class CustomerSaveTest extends AbstractBackendController
{
    /**
     * Base controller URL
     *
     * @var string
     */
    private $baseControllerUrl = 'backend/customer/index/';

    /** @var CustomerRepositoryInterface */
    private $customerRepository;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->customerRepository = $this->_objectManager->get(CustomerRepositoryInterface::class);
    }

    /**
     * Update customer with subscription and redirect to edit page.
     *
     * @magentoDataFixture Magento/CustomerCustomAttributes/_files/customer_with_custom_text_attribute.php
     * @return void
     */
    public function testUpdateCustomer(): void
    {
        /** @var CustomerData $customerData */
        $customerData = $this->customerRepository->get('JohnDoe@mail.com', 1);
        //change value from '123'
        $this->saveCustomerAndAssertData($customerData, '123456789');
        //add leading 0
        $this->saveCustomerAndAssertData($customerData, '0123456789');
        //remove leading 0
        $this->saveCustomerAndAssertData($customerData, '123456789');
    }

    /**
     * Save customer and assert result after save
     *
     * @param CustomerData $customerData
     * @param string $attributeValue
     * @return void
     */
    private function saveCustomerAndAssertData(CustomerData $customerData, string $attributeValue): void
    {
        $postData = $expectedData = [
            'customer' => [
                CustomerData::FIRSTNAME => 'John',
                CustomerData::LASTNAME => 'Doe',
            ],
            'test_text_attribute' => $attributeValue,
        ];
        $postData['customer']['entity_id'] = $customerData->getId();
        $params = ['back' => true];
        $this->dispatchCustomerSave($postData, $params);
        $this->assertSessionMessages(
            $this->equalTo([(string)__('You saved the customer.')]),
            MessageInterface::TYPE_SUCCESS
        );
        $this->assertRedirect($this->stringContains(
            $this->baseControllerUrl . 'edit/id/' . $customerData->getId()
        ));
        $this->assertCustomerData($customerData->getEmail(), (int)$customerData->getWebsiteId(), $expectedData);
    }

    /**
     * Create or update customer using backend/customer/index/save action.
     *
     * @param array $postData
     * @param array $params
     * @return void
     */
    private function dispatchCustomerSave(array $postData, array $params = []): void
    {
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue($postData);
        if (!empty($params)) {
            $this->getRequest()->setParams($params);
        }
        $this->dispatch($this->baseControllerUrl . 'save');
    }

    /**
     * Check that customer parameters match expected values.
     *
     * @param string $customerEmail
     * @param int $customerWebsiteId
     * @param array $expectedData
     * @return void
     */
    private function assertCustomerData(
        string $customerEmail,
        int $customerWebsiteId,
        array $expectedData
    ): void {
        /** @var CustomerData $customerData */
        $customerData = $this->customerRepository->get($customerEmail, $customerWebsiteId);
        $actualCustomerArray = $customerData->__toArray();
        foreach ($expectedData['customer'] as $key => $expectedValue) {
            $this->assertSame(
                $expectedValue,
                $actualCustomerArray[$key],
                "Invalid expected value for $key field."
            );
        }
    }
}
