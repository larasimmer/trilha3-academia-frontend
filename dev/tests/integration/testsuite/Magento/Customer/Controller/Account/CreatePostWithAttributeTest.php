<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Customer\Controller\Account;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Message\MessageInterface;
use Magento\TestFramework\TestCase\AbstractController;

/**
 * @magentoAppArea frontend
 */
class CreatePostWithAttributeTest extends AbstractController
{
    /**
     * @var CustomerRepositoryInterface
     */
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
     * Assert that customer with custom addres attribute in address successfully created.
     *
     * @magentoDataFixture Magento/CustomerCustomAttributes/_files/address_multiselect_attribute.php
     * @return void
     */
    public function testCustomerWithCustomAddressAttributeSuccessfullyCreated(): void
    {
        $customAttributeCode = 'multi_select_attribute_code';
        $postData = [
            AddressInterface::FIRSTNAME => 'John',
            AddressInterface::LASTNAME => 'Doe',
            AddressInterface::TELEPHONE => '555-555-555',
            AddressInterface::STREET => [
                0 => '12 Space Drive',
                1 => '',
            ],
            AddressInterface::CITY => 'Austin',
            AddressInterface::REGION_ID => '57',
            AddressInterface::REGION => '',
            AddressInterface::POSTCODE => '78758',
            AddressInterface::COUNTRY_ID => 'US',
            AddressInterface::DEFAULT_BILLING => '1',
            AddressInterface::DEFAULT_SHIPPING => '1',
            CustomerInterface::EMAIL => 'john@email.com',
            'password' => '123123qQ',
            'password_confirmation' => '123123qQ',
            'address' => [
                $customAttributeCode => [
                    0 => '1',
                    1 => '2',
                ],
            ],
            'create_address' => '1',
        ];

        $this->performRequestWithData($postData);
        $this->checkRequestPerformedSuccessfully();

        $customer = $this->customerRepository->get('john@email.com');
        $customerAddresses = $customer->getAddresses();
        $this->assertCount(1, $customerAddresses);

        /** @var AddressInterface $address */
        $address = reset($customerAddresses);
        $customAttribute = $address->getCustomAttribute($customAttributeCode);
        $this->assertNotNull($customAttribute);
        $this->assertEquals('1,2', $customAttribute->getValue());
    }

    /**
     * Perform request with provided POST data.
     *
     * @param array $postData
     * @return void
     */
    private function performRequestWithData(array $postData): void
    {
        $this->getRequest()->setPostValue($postData)->setMethod(Http::METHOD_POST);
        $this->dispatch('customer/account/createPost');
    }

    /**
     * Check that save address request performed successfully.
     *
     * @return void
     */
    private function checkRequestPerformedSuccessfully(): void
    {
        $this->assertRedirect($this->stringContains('customer/account'));
        $this->assertSessionMessages(
            $this->countOf(1),
            MessageInterface::TYPE_SUCCESS
        );
    }
}
