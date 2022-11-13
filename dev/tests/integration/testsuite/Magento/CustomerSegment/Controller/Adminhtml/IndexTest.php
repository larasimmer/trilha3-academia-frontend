<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CustomerSegment\Controller\Adminhtml;

use Magento\CustomerSegment\Model\ResourceModel\Segment as SegmentResource;
use Magento\CustomerSegment\Model\Segment;
use Magento\Logging\Model\Event;
use Magento\Logging\Model\ResourceModel\Event as LoggingResource;
use Magento\TestFramework\Helper\Xpath;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * @magentoAppArea adminhtml
 */
class IndexTest extends AbstractBackendController
{
    /**
     * @var SegmentResource
     */
    private $segmentResource;

    /**
     * @var LoggingResource
     */
    private $loggingResource;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->segmentResource = $this->_objectManager->create(
            SegmentResource::class
        );
        $this->loggingResource = $this->_objectManager->create(
            LoggingResource::class
        );
    }

    /**
     * Checks that all important blocks are successfully created and rendered
     *
     * @magentoDbIsolation enabled
     */
    public function testNewAction()
    {
        $this->dispatch('backend/customersegment/index/new/');
        $body = $this->getResponse()->getBody();
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(
                '//form[@id="edit_form"]',
                $body
            )
        );
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(
                '//*[@id="magento_customersegment_segment_tabs"]',
                $body
            )
        );
    }

    /**
     * Checks possibility to save customer segment
     *
     * @magentoDbIsolation enabled
     */
    public function testSaveAction()
    {
        $segment = $this->_objectManager->create(Segment::class);
        $this->segmentResource->load($segment, 'Customer Segment 1', 'name');
        $this->dispatch(
            'backend/customersegment/index/save/id/' . $segment->getId()
        );
        $content = $this->getResponse()->getBody();
        $this->assertStringNotContainsString(
            'Unable to save the segment.',
            $content
        );
    }

    /**
     * @magentoDataFixture Magento/CustomerSegment/_files/segment.php
     * @magentoDataFixture Magento/Customer/_files/customer.php
     */
    public function testMatchActionLogging()
    {
        /** @var Event $loggingModel */
        $loggingModel = $this->_objectManager->create(Event::class);
        $this->loggingResource->load(
            $loggingModel,
            'magento_customersegment',
            'event_code'
        );
        $this->assertEmpty($loggingModel->getId());

        $segment = $this->_objectManager->create(Segment::class);
        $this->segmentResource->load($segment, 'Customer Segment 1', 'name');
        $this->dispatch(
            'backend/customersegment/index/match/id/' . $segment->getId()
        );

        $this->loggingResource->load(
            $loggingModel,
            'magento_customersegment',
            'event_code'
        );
        $this->assertNotEmpty($loggingModel->getId());
        $expected = json_encode([
            'general' => __(
                'Matching Customers of Segment %1 is added to messages queue.',
                $segment->getId()
            )
        ]);
        $this->assertEquals($expected, $loggingModel->getInfo());
    }
}
