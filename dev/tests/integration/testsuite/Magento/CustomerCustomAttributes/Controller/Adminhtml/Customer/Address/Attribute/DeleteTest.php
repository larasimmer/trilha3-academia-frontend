<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Controller\Adminhtml\Customer\Address\Attribute;

use Magento\Customer\Model\Customer;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\ConfigFactory;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\MessageInterface;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * Class check customer attribute delete controller
 *
 * @see \Magento\CustomerCustomAttributes\Controller\Adminhtml\Customer\Address\Attribute\Delete
 *
 * @magentoAppArea adminhtml
 */
class DeleteTest extends AbstractBackendController
{
    /** @var AttributeRepositoryInterface */
    private $attributeRepository;

    /** @var Config */
    private $eavConfig;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->attributeRepository = $this->_objectManager->get(AttributeRepositoryInterface::class);
        $this->eavConfig = $this->_objectManager->get(ConfigFactory::class)->create();
    }

    /**
     * @return void
     */
    public function testWithoutAttributeId(): void
    {
        $this->dispatchWithParams(['attribute_id' => null]);
        $this->assertRedirect($this->stringContains('backend/admin/customer_address_attribute/index/'));
    }

    /**
     * @magentoDbIsolation disabled
     *
     * @magentoDataFixture Magento/CustomerCustomAttributes/_files/customer_attribute_type_select.php
     *
     * @return void
     */
    public function testCannotDeleteAttribute(): void
    {
        $attribute = $this->attributeRepository->get(Customer::ENTITY, 'customer_attribute_type_select');
        $this->dispatchWithParams(['attribute_id' => $attribute->getId()]);
        $this->assertSessionMessages(
            $this->containsEqual((string)__('You cannot delete this attribute.')),
            MessageInterface::TYPE_ERROR
        );
        $this->assertRedirect($this->stringContains('backend/admin/customer_address_attribute/index/'));
    }

    /**
     * @magentoDataFixture Magento/CustomerCustomAttributes/_files/address_custom_attribute_without_transaction.php
     *
     * @return void
     */
    public function testSuccessfulDelete(): void
    {
        $attribute = $this->eavConfig->getAttribute('customer_address', 'test_text_code');
        $this->dispatchWithParams(['attribute_id' => $attribute->getId()]);
        $this->assertSessionMessages($this->containsEqual((string)__('You deleted the customer address attribute.')));
        $this->assertRedirect($this->stringContains('backend/admin/customer_address_attribute/index/'));
        $this->expectException(NoSuchEntityException::class);
        $this->attributeRepository->get('customer_address', 'test_text_code');
    }

    /**
     * Dispatch request with params
     *
     * @param array $params
     * @return void
     */
    private function dispatchWithParams(array $params = []): void
    {
        $this->getRequest()->setParams($params);
        $this->getRequest()->setMethod(Http::METHOD_POST);
        $this->dispatch('backend/admin/customer_address_attribute/delete');
    }
}
