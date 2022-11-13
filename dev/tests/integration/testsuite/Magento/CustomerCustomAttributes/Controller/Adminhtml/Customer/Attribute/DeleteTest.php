<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Controller\Adminhtml\Customer\Attribute;

use Magento\Customer\Model\AttributeFactory;
use Magento\Customer\Model\Customer;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Message\MessageInterface;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * Test for delete customer attribute controller.
 *
 * @see \Magento\CustomerCustomAttributes\Controller\Adminhtml\Customer\Attribute\Delete
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class DeleteTest extends AbstractBackendController
{
    /** @var AttributeFactory */
    private $attributeFactory;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->attributeFactory = $this->_objectManager->get(AttributeFactory::class);
    }

    /**
     * @magentoDbIsolation disabled
     *
     * @magentoDataFixture Magento/CustomerCustomAttributes/_files/customer_attribute_type_select.php
     *
     * @return void
     */
    public function testDeleteAttribute(): void
    {
        $attribute = $this->attributeFactory->create()->loadByCode(Customer::ENTITY, 'customer_attribute_type_select');
        $this->assertNotNull($attribute->getId());
        $this->dispatchDeleteCustomerAttributeRequest($attribute->getId());
        $this->assertSessionMessages(
            $this->containsEqual((string)__('You deleted the customer attribute.')),
            MessageInterface::TYPE_SUCCESS
        );
        $this->assertRedirect($this->stringContains('backend/admin/customer_attribute/index'));
    }

    /**
     * @return void
     */
    public function testDeleteSystemAttribute(): void
    {
        $attribute = $this->attributeFactory->create()->loadByCode(Customer::ENTITY, 'gender');
        $this->assertNotNull($attribute->getId());
        $this->dispatchDeleteCustomerAttributeRequest($attribute->getId());
        $this->assertSessionMessages(
            $this->containsEqual((string)__('You cannot delete this attribute.')),
            MessageInterface::TYPE_ERROR
        );
        $this->assertRedirect($this->stringContains('backend/admin/customer_attribute/index'));
    }

    /**
     * @magentoDataFixture Magento/CustomerCustomAttributes/_files/address_multiselect_attribute.php
     *
     * @return void
     */
    public function testDeleteAnotherTypeAttribute(): void
    {
        $attribute = $this->attributeFactory->create()->loadByCode('customer_address', 'multi_select_attribute_code');
        $this->assertNotNull($attribute->getId());
        $this->dispatchDeleteCustomerAttributeRequest($attribute->getId());
        $this->assertSessionMessages(
            $this->containsEqual((string)__('You cannot delete this attribute.')),
            MessageInterface::TYPE_ERROR
        );
        $this->assertRedirect($this->stringContains('backend/admin/customer_attribute/index'));
    }

    /**
     * Dispatch delete customer attribute request.
     *
     * @param string $attributeId
     * @return void
     */
    private function dispatchDeleteCustomerAttributeRequest(string $attributeId): void
    {
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST)->setParam('attribute_id', $attributeId);
        $this->dispatch('backend/admin/customer_attribute/delete');
    }
}
