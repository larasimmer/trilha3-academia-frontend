<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Controller\Adminhtml\Rma;

use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Rma\Api\RmaRepositoryInterface;
use Magento\Rma\Model\Rma;
use Magento\Rma\Model\Rma\Source\Status;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * 'RMA grid' Controller integration tests.
 *
 * @magentoAppArea adminhtml
 */
class CloseTest extends AbstractBackendController
{
    /**
     * Check close RMA items
     *
     * @magentoDataFixture Magento/Rma/_files/rma_list.php
     * @return void
     */
    public function testCloseMultiple(): void
    {
        $rma = Bootstrap::getObjectManager()->create(Rma::class);
        $rma->load('100000002', 'increment_id');
        $rma1Id = $rma->getId();
        $rma->load('100000003', 'increment_id');
        $rma2Id = $rma->getId();

        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue(['entity_ids' => [$rma1Id, $rma2Id]]);
        $this->dispatch('backend/admin/rma/close');

        /** @var RmaRepositoryInterface $repository */
        $repository = $this->_objectManager->get(RmaRepositoryInterface::class);

        $rma1Result = $repository->get($rma1Id);
        $this->assertEquals(Status::STATE_CLOSED, $rma1Result->getStatus());

        $rma2Result = $repository->get($rma2Id);
        $this->assertEquals(Status::STATE_CLOSED, $rma2Result->getStatus());
    }
}
