<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws;

use Magento\Customer\Model\ResourceModel\Address\Attribute\Collection as CustomerAddressAttributeCollection;
use Magento\Customer\Model\ResourceModel\Attribute\Collection as CustomerAttributeCollection;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * @magentoAppArea adminhtml
 * @magentoDataFixture Magento/AdminGws/_files/role_websites_login.php
 */
class AssertButtonsRemovedTest extends \Magento\TestFramework\TestCase\AbstractBackendController
{
    protected function _getAdminCredentials()
    {
        return [
            'user' => 'admingws_user',
            'password' => 'admingws_password1'
        ];
    }

    public function testCustomerAttributeSaveButtonsShouldBeAbsent()
    {
        /** @var CustomerAttributeCollection $customerAttributeCollection */
        $customerAttributeCollection = Bootstrap::getObjectManager()->get(CustomerAttributeCollection::class);
        $customerAttributeCollection->getSelect()->limit(1);
        $customerAttributeItems = $customerAttributeCollection->getItems();
        $attributeId = array_pop($customerAttributeItems)->getAttributeId();
        $this->dispatch('backend/admin/customer_attribute/edit/attribute_id/' . $attributeId . '/');
        $this->assertEquals(200, $this->getResponse()->getHttpResponseCode());
        $this->assertStringNotContainsString('Save Attribute', $this->getResponse()->getBody());
        $this->assertStringNotContainsString('Save and Continue Edit', $this->getResponse()->getBody());
    }

    public function testCustomerAddressAttributeSaveButtonsShouldBeAbsent()
    {
        /** @var CustomerAddressAttributeCollection $customerAttributeCollection */
        $customerAddressAttributeCollection = Bootstrap::getObjectManager()
            ->get(CustomerAddressAttributeCollection::class);
        $customerAddressAttributeCollection->getSelect()->limit(1);
        $customerAddressAttributeItems = $customerAddressAttributeCollection->getItems();
        $attributeId = array_pop($customerAddressAttributeItems)->getAttributeId();
        $this->dispatch('backend/admin/customer_address_attribute/edit/attribute_id/' . $attributeId . '/');
        $this->assertEquals(200, $this->getResponse()->getHttpResponseCode());
        $this->assertStringNotContainsString('Save', $this->getResponse()->getBody());
        $this->assertStringNotContainsString('Save and Continue Edit', $this->getResponse()->getBody());
    }

    public function testRmaAttributeAddButtonShouldBeAbsent()
    {
        $this->dispatch('backend/admin/rma_item_attribute/index/');
        $this->assertEquals(200, $this->getResponse()->getHttpResponseCode());
        $this->assertStringNotContainsString('Add New Attribute', $this->getResponse()->getBody());
    }
}
