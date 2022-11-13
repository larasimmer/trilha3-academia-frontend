<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Magento\Shipping\Controller\Adminhtml\Order\Shipment;

use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\OrderRepository;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * @magentoAppArea adminhtml
 * @magentoDbIsolation disabled
 * @magentoAppIsolation enabled
 * @magentoDataFixture Magento/AdminGws/_files/role_on_second_website.php
 * @magentoDataFixture Magento/Sales/_files/order_on_second_website.php
 */
class ControllerTest extends AbstractBackendController
{
    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->orderRepository = $this->_objectManager->get(OrderRepository::class);
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        $superRole = $this->_objectManager->create(\Magento\Authorization\Model\Role::class);
        $superRole->load(\Magento\TestFramework\Bootstrap::ADMIN_ROLE_NAME, 'role_name');
        $gwsRole = $this->_objectManager->get(\Magento\AdminGws\Model\Role::class);
        $gwsRole->setAdminRole($superRole);
        parent::tearDown();
    }

    /**
     * @inheritdoc
     */
    protected function _getAdminCredentials()
    {
        return [
            'user' => 'customRoleUser',
            'password' => \Magento\TestFramework\Bootstrap::ADMIN_PASSWORD,
        ];
    }

    /**
     * Test that admin user with website level access can create shipping for order created on that website
     *
     * @magentoConfigFixture current_store catalog/price/scope 1
     * @magentoConfigFixture fixture_second_store_store catalog/price/scope 1
     */
    public function testCreateShippingForOrderCreatedInAllowedStore(): void
    {
        $order = $this->getOrder('100000001');
        $this->getRequest()->setParams(
            [
                'order_id' => $order->getEntityId(),
            ]
        );
        $this->dispatch('backend/admin/order_shipment/new');
        $this->assertEquals(200, $this->getResponse()->getHttpResponseCode());
    }

    /**
     * Test that admin user with website level access cannot create shipping for order created on another website
     *
     * @magentoDataFixture Magento/AdminGws/_files/role_on_second_website.php
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testCreateShippingForOrderCreatedInNotAllowedStore(): void
    {
        $order = $this->getOrder('100000001');
        $this->getRequest()->setParams(
            [
                'order_id' => $order->getEntityId(),
            ]
        );
        $this->dispatch('backend/admin/order_shipment/new');
        $this->assertEquals(404, $this->getResponse()->getHttpResponseCode());
    }

    /**
     * @param string $incrementalId
     * @return OrderInterface|null
     */
    private function getOrder(string $incrementalId): ?OrderInterface
    {
        /** @var SearchCriteria $searchCriteria */
        $searchCriteria = $this->_objectManager->create(SearchCriteriaBuilder::class)
            ->addFilter(OrderInterface::INCREMENT_ID, $incrementalId)
            ->create();

        $orders = $this->orderRepository->getList($searchCriteria)->getItems();
        /** @var OrderInterface|null $order */
        $order = reset($orders);

        return $order;
    }
}
