<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Block\Adminhtml\Rma\NewRma\Tab\Items\Order;

use Magento\Authorization\Model\Role;
use Magento\Backend\Model\Auth\Session;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Layout;
use Magento\Framework\View\LayoutInterface;
use Magento\Sales\Model\Order;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\User\Model\User;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 * @magentoAppIsolation enabled
 * @magentoDbIsolation disabled
 */
class GridTest extends TestCase
{
    /**
     * @magentoDataFixture Magento/Rma/_files/order_to_rma_for_restricted_admin.php
     */
    public function testToHtml(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $productRepository = $objectManager->get(ProductRepositoryInterface::class);
        $product = $productRepository->get('simple1000');

        $role = $objectManager->create(Role::class)->load('role_has_test_website_access_only', 'role_name');
        /** @var User $currentAdmin */
        $currentAdmin = $objectManager->create(User::class)
            ->loadByUsername('johnAdmin' . $role->getId());
        /** @var Session $authSession */
        $authSession = $objectManager->create(Session::class);
        $authSession->setUser($currentAdmin);

        $order = $objectManager->create(Order::class);
        $order->loadByIncrementId('100000001');
        $objectManager->get(Registry::class)->unregister('current_order');
        $objectManager->get(Registry::class)->register('current_order', $order);

        /** @var $layout Layout */
        $layout = $objectManager->get(LayoutInterface::class);
        /* @var Grid $block */
        $block = $layout->createBlock(Grid::class, 'grid');

        $html = $block->toHtml();
        $this->assertMatchesRegularExpression('/\>[\s]*0[\s]*\<\/span\>[\s]*records found/', $html);
        $this->assertStringNotContainsString(' id="id_' . $product->getId() . '"', $html);
        $this->assertCount(0, $block->getCollection());
    }
}
