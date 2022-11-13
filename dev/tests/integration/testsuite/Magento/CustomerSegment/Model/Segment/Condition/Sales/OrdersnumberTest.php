<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerSegment\Model\Segment\Condition\Sales;

use Magento\CustomerSegment\Model\Segment;
use Magento\CustomerSegment\Model\Segment\Condition\Combine\Root;
use Magento\CustomerSegment\Model\Segment\Condition\Order\Status;
use Magento\CustomerSegment\Model\Segment\Condition\Uptodate;
use Magento\Sales\Model\Order;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Tests Ordersnumber condition with customer segment
 *
 * @magentoDataFixture Magento/CustomerSegment/_files/segment_with_three_customers_and_two_past_orders.php
 */
class OrdersnumberTest extends TestCase
{
    const CUSTOMER_1 = 1;
    const CUSTOMER_2 = 2;
    const CUSTOMER_3 = 3;
    const WEBSITE = 1;

    /**
     * Tests matched customers using orders number condition
     *
     * @dataProvider getSatisfiedIdsDataProvider
     * @param array $conditions
     * @param array $expectedCustomerIds
     */
    public function testGetSatisfiedIds(array $conditions, array $expectedCustomerIds)
    {
        $segment = $this->saveSegment($conditions);
        $actualCustomerIds = $segment->getConditions()->getSatisfiedIds(self::WEBSITE);
        sort($actualCustomerIds, SORT_NUMERIC);
        $actualCustomerIds = array_values($actualCustomerIds);
        sort($expectedCustomerIds, SORT_NUMERIC);
        $actualCustomerIds = array_values($actualCustomerIds);
        $this->assertEquals($expectedCustomerIds, $actualCustomerIds);
    }

    /**
     * Tests matched customer using orders number condition
     *
     * @dataProvider getSatisfiedIdsDataProvider
     * @param array $conditions
     * @param array $expectedCustomerIds
     */
    public function testIsSatisfiedBy(array $conditions, array $expectedCustomerIds)
    {
        $segment = $this->saveSegment($conditions);
        foreach ([self::CUSTOMER_1, self::CUSTOMER_2, self::CUSTOMER_3] as $customerId) {
            $isCustomerNotExpectedToSatisfy = !in_array($customerId, $expectedCustomerIds);
            $isCustomerActuallySatisfy = $segment->getConditions()->isSatisfiedBy($customerId, self::WEBSITE, []);
            $this->assertTrue(
                $isCustomerNotExpectedToSatisfy xor $isCustomerActuallySatisfy,
                "Customer ID#$customerId Failed"
            );
        }
    }

    /**
     * @param array $conditions
     * @return Segment
     * @throws \Exception
     */
    private function saveSegment(array $conditions): Segment
    {
        $objectManager = Bootstrap::getObjectManager();
        $segmentModel = $objectManager->create(Segment::class);
        /** @var $segment Segment */
        $segment = $segmentModel->load('Customer Segment 1', 'name');
        $data['segment_id'] = $segment->getSegmentId();
        $data['conditions'] = $conditions;
        $segment->loadPost($data);
        $segment->save();
        $segment->matchCustomers();
        return $segment;
    }

    /**
     * Provides different conditions value with orders number condition
     *
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getSatisfiedIdsDataProvider(): array
    {
        return [
            'customers with at least one order' => [
                'conditions' => [
                    '1' => [
                        'type' => Root::class,
                        'aggregator' => 'all',
                        'value' => '1',
                        'new_child' => '',
                    ],
                    '1--1' => [
                        'type' => Ordersnumber::class,
                        'operator' => '>',
                        'value' => 0,
                        'aggregator' => 'all',
                        'new_child' => '',
                    ],
                ],
                'customers' => [
                    self::CUSTOMER_1,
                    self::CUSTOMER_2,
                ]
            ],
            'customers with zero or more orders' => [
                'conditions' => [
                    '1' => [
                        'type' => Root::class,
                        'aggregator' => 'all',
                        'value' => '1',
                        'new_child' => '',
                    ],
                    '1--1' => [
                        'type' => Ordersnumber::class,
                        'operator' => '>=',
                        'value' => 0,
                        'aggregator' => 'all',
                        'new_child' => '',
                    ],
                ],
                'customers' => [
                    self::CUSTOMER_1,
                    self::CUSTOMER_2,
                    self::CUSTOMER_3,
                ]
            ],
            'customers with zero orders' => [
                'conditions' => [
                    '1' => [
                        'type' => Root::class,
                        'aggregator' => 'all',
                        'value' => '1',
                        'new_child' => '',
                    ],
                    '1--1' => [
                        'type' => Ordersnumber::class,
                        'operator' => '==',
                        'value' => 0,
                        'aggregator' => 'all',
                        'new_child' => '',
                    ],
                ],
                'customers' => [
                    self::CUSTOMER_3,
                ]
            ],
            'customers with zero orders in the past 30 days' => [
                'conditions' => [
                    '1' => [
                        'type' => Root::class,
                        'aggregator' => 'all',
                        'value' => '1',
                        'new_child' => '',
                    ],
                    '1--1' => [
                        'type' => Ordersnumber::class,
                        'operator' => '==',
                        'value' => 0,
                        'aggregator' => 'all',
                        'new_child' => '',
                    ],
                    '1--1--1' => [
                        'type' => Uptodate::class,
                        'operator' => '>=',
                        'value' => 30,
                    ],
                ],
                'customers' => [
                    self::CUSTOMER_2,
                    self::CUSTOMER_3,
                ]
            ],
            'customers with at least one order in the past 30 days' => [
                'conditions' => [
                    '1' => [
                        'type' => Root::class,
                        'aggregator' => 'all',
                        'value' => '1',
                        'new_child' => '',
                    ],
                    '1--1' => [
                        'type' => Ordersnumber::class,
                        'operator' => '>',
                        'value' => 0,
                        'aggregator' => 'all',
                        'new_child' => '',
                    ],
                    '1--1--1' => [
                        'type' => Uptodate::class,
                        'operator' => '>=',
                        'value' => 30,
                    ],
                ],
                'customers' => [
                    self::CUSTOMER_1,
                ]
            ],
            'customers with at least one processing order in the past 30 days' => [
                'conditions' => [
                    '1' => [
                        'type' => Root::class,
                        'aggregator' => 'all',
                        'value' => '1',
                        'new_child' => '',
                    ],
                    '1--1' => [
                        'type' => Ordersnumber::class,
                        'operator' => '>',
                        'value' => 0,
                        'aggregator' => 'all',
                        'new_child' => '',
                    ],
                    '1--1--1' => [
                        'type' => Uptodate::class,
                        'operator' => '>=',
                        'value' => 30,
                    ],
                    '1--1--2' => [
                        'type' => Status::class,
                        'operator' => '==',
                        'value' => Order::STATE_PROCESSING,
                    ],
                ],
                'customers' => [
                    self::CUSTOMER_1,
                ]
            ],
            'customers with at least one complete order in the past 30 days' => [
                'conditions' => [
                    '1' => [
                        'type' => Root::class,
                        'aggregator' => 'all',
                        'value' => '1',
                        'new_child' => '',
                    ],
                    '1--1' => [
                        'type' => Ordersnumber::class,
                        'operator' => '>',
                        'value' => 0,
                        'aggregator' => 'all',
                        'new_child' => '',
                    ],
                    '1--1--1' => [
                        'type' => Uptodate::class,
                        'operator' => '>=',
                        'value' => 30,
                    ],
                    '1--1--2' => [
                        'type' => Status::class,
                        'operator' => '==',
                        'value' => Order::STATE_COMPLETE,
                    ],
                ],
                'customers' => [
                ]
            ],
        ];
    }
}
