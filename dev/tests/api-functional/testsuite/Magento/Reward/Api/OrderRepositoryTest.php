<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Api;

use Magento\TestFramework\TestCase\WebapiAbstract;

class OrderRepositoryTest extends WebapiAbstract
{
    const RESOURCE_PATH = '/V1/orders';

    const SERVICE_READ_NAME = 'salesOrderRepositoryV1';

    const SERVICE_VERSION = 'V1';

    const ORDER_INCREMENT_ID = '100000001';

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
    }

    /**
     * @magentoApiDataFixture Magento/Reward/_files/order_with_reward_info.php
     */
    public function testOrderGet()
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->objectManager->create(\Magento\Sales\Model\Order::class);
        $order->loadByIncrementId(self::ORDER_INCREMENT_ID);

        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . '/' . $order->getId(),
                'httpMethod' => \Magento\Framework\Webapi\Rest\Request::HTTP_METHOD_GET,
            ],
            'soap' => [
                'service' => self::SERVICE_READ_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_READ_NAME . 'get',
            ],
        ];
        $result = $this->_webApiCall($serviceInfo, ['id' => $order->getId()]);

        $this->assertEquals(100, $result['extension_attributes']['reward_points_balance']);
        $this->assertEquals(15.1, $result['extension_attributes']['reward_currency_amount']);
        $this->assertEquals(14.9, $result['extension_attributes']['base_reward_currency_amount']);
    }

    /**
     * @magentoApiDataFixture Magento/Reward/_files/order_list_with_reward_info.php
     */
    public function testOrderGetList()
    {
        /** @var \Magento\Framework\Api\SortOrderBuilder $sortOrderBuilder */
        $sortOrderBuilder = $this->objectManager->get(
            \Magento\Framework\Api\SortOrderBuilder::class
        );
        /** @var $searchCriteriaBuilder  \Magento\Framework\Api\SearchCriteriaBuilder */
        $searchCriteriaBuilder = $this->objectManager->create(
            \Magento\Framework\Api\SearchCriteriaBuilder::class
        );

        /** @var $filterBuilder  \Magento\Framework\Api\FilterBuilder */
        $filterBuilder = $this->objectManager->create(
            \Magento\Framework\Api\FilterBuilder::class
        );
        $filter1 = $filterBuilder
            ->setField('status')
            ->setValue('processing')
            ->setConditionType('eq')
            ->create();
        $filter2 = $filterBuilder
            ->setField('state')
            ->setValue(\Magento\Sales\Model\Order::STATE_NEW)
            ->setConditionType('eq')
            ->create();
        $filter3 = $filterBuilder
            ->setField('increment_id')
            ->setValue('100000001')
            ->setConditionType('eq')
            ->create();
        $sortOrder = $sortOrderBuilder->setField('grand_total')
            ->setDirection('DESC')
            ->create();
        $searchCriteriaBuilder->addFilters([$filter1]);
        $searchCriteriaBuilder->addFilters([$filter2, $filter3]);
        $searchCriteriaBuilder->addSortOrder($sortOrder);
        $searchData = $searchCriteriaBuilder->create()->__toArray();

        $requestData = ['searchCriteria' => $searchData];
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . '?' . http_build_query($requestData),
                'httpMethod' => \Magento\Framework\Webapi\Rest\Request::HTTP_METHOD_GET,
            ],
            'soap' => [
                'service' => self::SERVICE_READ_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_READ_NAME . 'getList',
            ],
        ];

        $result = $this->_webApiCall($serviceInfo, $requestData);
        $this->assertEquals(100, $result['items'][0]['extension_attributes']['reward_points_balance']);
        $this->assertEquals(15.1, $result['items'][0]['extension_attributes']['reward_currency_amount']);
        $this->assertEquals(14.9, $result['items'][0]['extension_attributes']['base_reward_currency_amount']);
    }
}
