<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Controller\Adminhtml\Customer\Reward;

use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractBackendController;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\ResourceModel\Customer\Collection;

/**
 * Test for class \Magento\Reward\Controller\Customer\Reward\DeleteOrphanPoints
 *
 * @magentoAppArea adminhtml
 */
class DeleteOrphanPointsTest extends AbstractBackendController
{

    /** Test Delete Orphan points
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Reward/_files/create_orphanpoints.php
     * @return void
     */
    public function testRemoveOrphanPoints(): void
    {
        $customer= $this->getFixtureCustomer();
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue(['id' => $customer->getId()]);
        $this->dispatch('backend/admin/customer_reward/deleteorphanpoints');
        $this->assertSessionMessages($this->equalTo([(string)__('You removed the orphan points.')]));
    }

    /**
     * Gets Customer Fixture.
     *
     * @return Customer
     */
    private function getFixtureCustomer(): Customer
    {
        /** @var Collection $customerCollection */
        $customerCollection = Bootstrap::getObjectManager()->create(Collection::class);
        return $customerCollection->getLastItem();
    }
}
