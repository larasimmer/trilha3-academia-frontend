<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Block\Adminhtml\Customer\Edit;

use Magento\Backend\Model\Search\AuthorizationMock;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Controller\RegistryConstants;
use Magento\Framework\Authorization;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Class checks Manage shopping cart button visibility
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class ManageShoppingCartButtonTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var ManageShoppingCartButton */
    private $button;

    /** @var CustomerRepositoryInterface */
    private $customerRepository;

    /** @var Registry */
    private $registry;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->objectManager->addSharedInstance(
            $this->objectManager->get(AuthorizationMock::class),
            Authorization::class
        );
        $this->button = $this->objectManager->get(ManageShoppingCartButton::class);
        $this->customerRepository = $this->objectManager->get(CustomerRepositoryInterface::class);
        $this->registry = $this->objectManager->get(Registry::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        $this->registry->unregister(RegistryConstants::CURRENT_CUSTOMER_ID);

        parent::tearDown();
    }

    /**
     * @return void
     */
    public function testGetButtonDataWithoutCustomer(): void
    {
        $this->assertEmpty($this->button->getButtonData());
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     *
     * @return void
     */
    public function testGetButtonDataWithCustomer(): void
    {
        $this->registry->register(RegistryConstants::CURRENT_CUSTOMER_ID, 1);
        $data = $this->button->getButtonData();
        $this->assertEquals(__('Manage Shopping Cart'), $data['label']);
        $this->assertStringContainsString('checkout/index/index/customer/1/', $data['on_click']);
    }
}
