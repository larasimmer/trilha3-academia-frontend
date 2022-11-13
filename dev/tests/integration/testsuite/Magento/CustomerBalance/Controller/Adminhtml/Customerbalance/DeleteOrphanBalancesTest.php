<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerBalance\Controller\Adminhtml\Customerbalance;

use Magento\CustomerBalance\Model\BalanceFactory;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * Test for delete orphan balances.
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class DeleteOrphanBalancesTest extends AbstractBackendController
{
    /** @var BalanceFactory */
    private $balanceFactory;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->balanceFactory = $this->_objectManager->get(BalanceFactory::class);
    }

    /**
     * @magentoDataFixture Magento/CustomerBalance/_files/customer_balance_without_website_id.php
     *
     * @return void
     */
    public function testDeleteOrphanBalances(): void
    {
        $this->markTestSkipped('Test blocked by issue MC-33308');
        $customerId = 1;
        $this->getRequest()->setPostValue(['id' => $customerId]);
        $this->getRequest()->setMethod(HttpRequest::METHOD_GET);
        $this->dispatch('backend/admin/customerbalance/deleteOrphanBalances');
        $this->assertRedirect($this->stringContains('backend/customer/index/edit'));
        $this->assertEquals(0, $this->balanceFactory->create()->getOrphanBalancesCount($customerId));
    }
}
