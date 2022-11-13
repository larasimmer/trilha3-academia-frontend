<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\WebsiteRestriction\Controller\Shipment\Tracking;

use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Url\EncoderInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\OrderRepository;
use Magento\TestFramework\TestCase\AbstractController;

/**
 * Test for get Tracking Info with Website Restrictions mode
 */
class PopupTest extends AbstractController
{
    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var EncoderInterface
     */
    private $urlEncoder;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->orderRepository = $this->_objectManager->get(OrderRepository::class);
        $this->urlEncoder = $this->_objectManager->get(EncoderInterface::class);
    }

    /**
     * @magentoConfigFixture current_store general/restriction/is_active 1
     * @magentoConfigFixture current_store general/restriction/mode 1
     * @magentoConfigFixture current_store general/restriction/http_status 1
     * @magentoDataFixture Magento/Shipping/_files/track.php
     */
    public function testIndexAction(): void
    {
        $order = $this->getOrder('100000001');
        $popupUrl = $this->getPopupUrl($order);

        $this->dispatch($popupUrl);
        $this->assertEquals(200, $this->getResponse()->getHttpResponseCode());
        $this->assertStringContainsString('track_number', $this->getResponse()->getBody());
    }

    /**
     * @param OrderInterface $order
     * @return string
     */
    private function getPopupUrl(OrderInterface $order): string
    {
        $hash = "order_id:{$order->getEntityId()}:{$order->getProtectCode()}";
        return 'shipping/tracking/popup?hash=' . $this->urlEncoder->encode($hash);
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
        /** @var OrderInterface $order */
        $order = reset($orders);

        return $order;
    }
}
