<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerSegment\Model;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Registry;
use Magento\Sales\Model\OrderRepository;
use Magento\Store\Model\Website;
use PHPUnit\Framework\TestCase;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\MessageQueue\PublisherConsumerController;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\MessageQueue\EnvironmentPreconditionException;
use Magento\TestFramework\MessageQueue\PreconditionFailedException;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\CustomerSegment\Model\Customer as CustomerSegment;
use Magento\CustomerSegment\Model\ResourceModel\Segment as SegmentResource;
use Magento\CustomerSegment\Model\ResourceModel\Segment\Report\Detail\Collection;

/**
 * Test matched customers segment with queue
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation disabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SegmentMatchConsumerTest extends TestCase
{
    /** @var PublisherConsumerController */
    private $publisherConsumerController;

    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var CustomerRepositoryInterface */
    private $customerRepository;

    /** @var Segment */
    private $segment;

    /** @var SegmentResource */
    private $segmentResource;

    /** @var string[] */
    private $consumers = ['matchCustomerSegmentProcessor'];

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->customerRepository = $this->objectManager->get(CustomerRepositoryInterface::class);
        $this->segment = $this->objectManager->get(Segment::class);
        $this->segmentResource = $this->objectManager->get(SegmentResource::class);
        $this->publisherConsumerController = Bootstrap::getObjectManager()->create(
            PublisherConsumerController::class,
            [
                'consumers' => $this->consumers,
                'logFilePath' => TESTS_TEMP_DIR . "/MessageQueueTestLog.txt",
                'maxMessages' => null,
                'appInitParams' => Bootstrap::getInstance()->getAppInitParams()
            ]
        );
        try {
            $this->publisherConsumerController->startConsumers();
        } catch (EnvironmentPreconditionException $e) {
            $this->markTestSkipped($e->getMessage());
        } catch (PreconditionFailedException $e) {
            $this->fail(
                $e->getMessage()
            );
        }
        parent::setUp();
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        $this->publisherConsumerController->stopConsumers();
        parent::tearDown();
    }

    /**
     * Wait for an asynchronous result from segment customer resource model
     *
     * @param $customer
     * @return void
     * @throws PreconditionFailedException
     */
    private function waitForAsynchronousResult(CustomerInterface $customer): void
    {
        $customerSegment = $this->objectManager->create(CustomerSegment::class);
        sleep(15); // timeout to processing Magento queue
        $this->publisherConsumerController->waitForAsynchronousResult(
            function ($customerId, $websiteId) use ($customerSegment) {
                return $customerSegment->getCustomerSegmentIdsForWebsite($customerId, $websiteId);
            },
            [$customer->getId(), $customer->getWebsiteId()]
        );
    }

    /**
     * Wait for asynchronous result from segment resource model
     *
     * @param $segment
     * @param $customersCount
     * @throws PreconditionFailedException
     */
    private function waitForAsynchronousResultFromSegment($segment, $customersCount): void
    {
        $segmentResource = $this->objectManager->create(SegmentResource::class);
        sleep(10); // timeout to processing Magento queue
        $this->publisherConsumerController->waitForAsynchronousResult(
            function ($id, $count) use ($segmentResource) {
                return $count === $segmentResource->getSegmentCustomersQty($id);
            },
            [$segment->getId(), $customersCount]
        );
    }

    /**
     * Matched customers segment from queue
     *
     * @return void
     * @magentoDataFixture Magento/CustomerSegment/_files/segment_customers.php
     */
    public function testMatchCustomersSuccessfully():void
    {
        $segment = $this->segment->load('Customer Segment 1', 'name');
        $customerSegment = $this->objectManager->create(CustomerSegment::class);
        $segment->matchCustomers();
        /** @var CustomerInterface $customer */
        $customer = $this->customerRepository->get('customer@search.example.com');
        $this->waitForAsynchronousResult($customer);
        $this->assertNotEmpty(
            $customerSegment->getCustomerSegmentIdsForWebsite($customer->getId(), $customer->getWebsiteId())
        );
    }

    /**
     * Tests customer segments for a website with account sharing enabled
     *
     * @return void
     * @magentoDataFixture Magento/CustomerSegment/_files/segment_multiwebsite.php
     * @magentoConfigFixture current_store customer/account_share/scope 0
     */
    public function testGetCustomerWebsiteSegmentsAccountSharingEnabled(): void
    {
        $mainWebsite = $this->objectManager->create(Website::class)->load('base');
        $secondWebsite = $this->objectManager->create(Website::class)->load('secondwebsite');
        $segment = $this->segment->load('Customer Segment Multi-Website', 'name');
        $customer = $this->customerRepository->get('customer@example.com');
        $customerSegment = $this->objectManager->create(CustomerSegment::class);
        $this->waitForAsynchronousResult($customer);
        $this->assertEquals(
            [$segment->getId()],
            $customerSegment->getCustomerSegmentIdsForWebsite($customer->getId(), $mainWebsite->getId())
        );
        $this->assertEquals(
            [$segment->getId()],
            $customerSegment->getCustomerSegmentIdsForWebsite($customer->getId(), $secondWebsite->getId())
        );
    }

    /**
     * Tests customer segments for a website with account sharing disabled
     *
     * @return void
     * @magentoDataFixture Magento/CustomerSegment/_files/segment_multiwebsite.php
     * @magentoConfigFixture current_store customer/account_share/scope 1
     */
    public function testGetCustomerWebsiteSegmentsWithAccountSharingDisabled(): void
    {
        $mainWebsite = $this->objectManager->create(Website::class)->load('base');
        $secondWebsite = $this->objectManager->create(Website::class)->load('secondwebsite');
        $customerSegment = $this->objectManager->create(CustomerSegment::class);
        $segment = $this->segment->load('Customer Segment Multi-Website', 'name');
        $customer = $this->customerRepository->get('customer@example.com');
        $this->waitForAsynchronousResult($customer);
        $this->assertEquals(
            [$segment->getId()],
            $customerSegment->getCustomerSegmentIdsForWebsite($customer->getId(), $mainWebsite->getId())
        );
        $this->assertEquals(
            [],
            $customerSegment->getCustomerSegmentIdsForWebsite($customer->getId(), $secondWebsite->getId())
        );
    }

    /**
     * Test segment with order condition matches only customers that have orders matching these conditions
     * Covers MAGETWO-67619
     *
     * @return void
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoDataFixture Magento/Customer/_files/two_customers.php
     * @magentoDataFixture Magento/CustomerSegment/_files/segment_with_order_history_conditions.php
     */
    public function testCustomerMatchByOrderedProducts(): void
    {
        $segment =  $this->segment->load('Segment with order history condition', 'name');
        $customerSegment = $this->objectManager->create(CustomerSegment::class);
        $orderRepository = $this->objectManager->get(OrderRepository::class);
        $orders = $orderRepository->getList($this->objectManager->get(SearchCriteriaInterface::class))->getItems();
        $order = array_pop($orders);
        $order->setCustomerId(1)->setCustomerIsGuest(false);
        $orderRepository->save($order);
        $segment->matchCustomers();
        $customer = $this->customerRepository->get('customer@example.com');
        $this->waitForAsynchronousResult($customer);
        /** @var Registry $registry */
        $registry = $this->objectManager->get(Registry::class);
        $registry->register('current_customer_segment', $segment);
        /** @var Collection $gridCollection */
        $gridCollection = $this->objectManager->get(Collection::class);
        $gridCollection->loadData();
        $this->assertCustomerCollectionData($gridCollection->getData());

        // Emulate other customer login event is processed
        $customerSegment->processCustomerEvent('customer_login', 2);

        // recreate collection as it is loading only if isLoaded flag is reset
        /** @var Collection $gridCollection */
        $gridCollection->resetData();
        $gridCollection->loadData();

        $this->assertCustomerCollectionData($gridCollection->getData());

        // Process invalid customer login
        $customerSegment->processCustomerEvent('customer_login', null);

        $gridCollection->resetData();
        $gridCollection->loadData();

        $this->assertCustomerCollectionData($gridCollection->getData());
    }

    /**
     * Matched customers segment from queue
     *
     * @return void
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoDataFixture Magento/Customer/_files/two_customers.php
     * @magentoDataFixture Magento/CustomerSegment/_files/segment.php
     */
    public function testMatchCustomersWithConditions():void
    {
        /** @var CustomerInterface $customer */
        $customer = $this->customerRepository->get('customer@example.com');
        $this->waitForAsynchronousResult($customer);
        $segment = $this->segment->load('Customer Segment 1', 'name');
        $countCustomersBeforeConditions = $this->segmentResource->getSegmentCustomersQty($segment->getId());
        $data = $this->getConditions();
        $segmentData = $segment->getData();
        $segmentData = array_merge($segmentData, $data['sales_amount_total_100_and_data_range']);
        $segment->loadPost($segmentData);
        $segment->save();
        $customerSegment = $this->objectManager->create(CustomerSegment::class);
        $orderRepository = $this->objectManager->get(OrderRepository::class);
        $orders = $orderRepository->getList($this->objectManager->get(SearchCriteriaInterface::class))->getItems();
        $order = array_pop($orders);
        $order->setCustomerId(1)->setCustomerIsGuest(false);
        $orderRepository->save($order);
        $segment->matchCustomers();
        $this->waitForAsynchronousResult($customer);
        $resultAfterFirstConditions = $customerSegment->getCustomerSegmentIdsForWebsite(
            $customer->getId(),
            $customer->getWebsiteId()
        );
        $countCustomersAfterFirstConditions = $this->segmentResource->getSegmentCustomersQty($segment->getId());
        $segmentData = array_merge($segmentData, $data['sales_amount_total_110_and_data_range']);
        $segment->loadPost($segmentData);
        $segment->save();
        $segment->matchCustomers();
        $this->waitForAsynchronousResultFromSegment($segment, 0);
        $countCustomersAfterSecondConditions = $this->segmentResource->getSegmentCustomersQty($segment->getId());
        $this->assertEquals(2, $countCustomersBeforeConditions);
        $this->assertEquals(1, $countCustomersAfterFirstConditions);
        $this->assertEquals(0, $countCustomersAfterSecondConditions);
        $this->assertEquals([$segment->getId()], $resultAfterFirstConditions);
    }

    /**
     * Get conditions for segment
     *
     * @return string[][][][]
     */
    private function getConditions(): array
    {
        return [
            'sales_amount_total_100_and_data_range' => [
                'conditions' => [
                    '1' => [
                        'type' => 'Magento\\CustomerSegment\\Model\\Segment\\Condition\\Combine\\Root',
                        'aggregator' => 'all',
                        'value' => '1',
                        'new_child' => '',
                    ],
                    '1--1' => [
                        'type' => 'Magento\\CustomerSegment\\Model\\Segment\\Condition\\Sales\\Salesamount',
                        'attribute' => 'total',
                        'operator' => '==',
                        'value' => '100',
                        'aggregator' => 'all',
                        'new_child' => '',
                    ],
                    '1--1--1' => [
                        'type' => 'Magento\\CustomerSegment\\Model\\Segment\\Condition\\Daterange',
                        'operator' => '==',
                        'value' => '2015-01-01...2025-01-01',
                    ],
                ],
            ],
            'sales_amount_total_110_and_data_range' => [
                'conditions' => [
                    '1' => [
                        'type' => 'Magento\\CustomerSegment\\Model\\Segment\\Condition\\Combine\\Root',
                        'aggregator' => 'all',
                        'value' => '1',
                        'new_child' => '',
                    ],
                    '1--1' => [
                        'type' => 'Magento\\CustomerSegment\\Model\\Segment\\Condition\\Sales\\Salesamount',
                        'attribute' => 'total',
                        'operator' => '==',
                        'value' => '110',
                        'aggregator' => 'all',
                        'new_child' => '',
                    ],
                    '1--1--1' => [
                        'type' => 'Magento\\CustomerSegment\\Model\\Segment\\Condition\\Daterange',
                        'operator' => '==',
                        'value' => '2015-01-01...2025-01-01',
                    ],
                ],
            ],
        ];
    }

    /**
     * Perform assertions on collection data
     *
     * @param $data
     * @return void
     */
    protected function assertCustomerCollectionData($data): void
    {
        $this->assertNotEmpty($data, 'Segment customer matching result is empty');
        $this->assertCount(1, $data, 'Segment should match only 1 costomer');
        $customerData = $data[0];
        $this->assertEquals('1', $customerData['entity_id'], 'Customer ID is not matching.');
        $this->assertEquals('1', $customerData['website_id'], 'Customer Website is not matching');
        $this->assertEquals('customer@example.com', $customerData['email'], 'Customer email is not matching');
    }
}
