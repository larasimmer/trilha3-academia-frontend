<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Model\Status;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Mail\EmailMessageInterface;
use Magento\Rma\Api\RmaRepositoryInterface;
use Magento\Rma\Model\Rma\Status\History;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Mail\Template\TransportBuilderMock;
use Magento\Rma\Model\Rma;

/**
 * @magentoAppArea adminhtml
 */
class HistoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var TransportBuilderMock
     */
    private $transportBuilder;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->transportBuilder = $this->objectManager->get(TransportBuilderMock::class);
    }

    /**
     * Checks that email is sent from address according to RMA store.
     *
     * @magentoConfigFixture fixturestore_store trans_email/ident_sales/email fixturestore@example.com
     * @magentoDataFixture Magento/Rma/_files/rma_fixture_store.php
     * @magentoDbIsolation disabled
     */
    public function testEmailFromForNonDefaultStore()
    {
        $rmaId = '2';
        $rma = $this->getRma($rmaId);

        /** @var History $statusHistory */
        $statusHistory = $this->objectManager->create(History::class);
        $statusHistory->setRmaEntityId($rma->getEntityId());
        $statusHistory->sendNewRmaEmail();

        /** @var EmailMessageInterface $message */
        $message = $this->transportBuilder->getSentMessage();
        $this->assertNotEmpty($message->getFrom());

        /** @var \Magento\Framework\Mail\Address $mailFromAddress */
        $mailFromAddress = current($message->getFrom());
        $this->assertEquals('fixturestore@example.com', $mailFromAddress->getEmail());
    }

    /**
     * Loads RMA entity by increment ID.
     *
     * @param string $incrementId
     * @return Rma
     */
    private function getRma(string $incrementId): Rma
    {
        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        $searchCriteria = $searchCriteriaBuilder->addFilter('increment_id', $incrementId)
            ->create();

        /** @var RmaRepositoryInterface $repository */
        $repository = $this->objectManager->get(RmaRepositoryInterface::class);
        $items = $repository->getList($searchCriteria)
            ->getItems();

        return array_pop($items);
    }
}
