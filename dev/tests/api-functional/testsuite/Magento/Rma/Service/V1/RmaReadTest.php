<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Rma\Service\V1;

use Magento\Framework\Webapi\Rest\Request;
use Magento\Rma\Model\Rma;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\TestCase\WebapiAbstract;

/**
 * @magentoApiDataFixture Magento/Rma/_files/rma.php
 */
class RmaReadTest extends WebapiAbstract
{
    /**#@+
     * Constants defined for Web Api call
     */
    const SERVICE_VERSION = 'V1';
    const SERVICE_NAME_SEARCH = 'rmaRmaManagementV1';
    const SERVICE_NAME_GET = 'rmaRmaRepositoryV1';
    /**#@-*/

    /**
     * Rma Items
     *
     * @var array
     */
    private $rmaItems = [
        [
            'is_qty_decimal'     => "0",
            'qty_requested'      => "2",
            'qty_authorized'     => "2",
            'qty_approved'       => "2",
            'status'             => "processing",
            'product_name'       => "Simple Product",
            'qty_returned'       => "2",
            'product_sku'        => "simple",
            'product_admin_name' => null,
            'product_admin_sku'  => null,
            'product_options'    => null,
        ],
    ];

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager = Bootstrap::getObjectManager();
    }

    public function testGet()
    {
        $rma = $this->getRmaFixture();
        $rmaId = (int)$rma->getId();

        $serviceInfo = [
            'rest' => [
                'resourcePath' => '/V1/returns/' . $rmaId,
                'httpMethod' => Request::HTTP_METHOD_GET,
            ],
            'soap' => [
                'service' => self::SERVICE_NAME_GET,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_NAME_GET . 'get',
            ],
        ];

        $result = $this->_webApiCall($serviceInfo, ['id' => $rmaId]);

        self::assertEquals($rmaId, $result[Rma::ENTITY_ID]);
        $this->performAsserts($this->rmaItems, $result['items'], 'Rma Items are not correct');

        $rmaComments = $this->getRmaComments($rmaId);
        $this->performAsserts($rmaComments['items'], $result['comments'], 'Rma Comments are not correct');

        $rmaTracks = $this->getRmaTracks($rmaId);
        self::assertNotEmpty($result['tracks']);
        $this->performAsserts($rmaTracks['items'], $result['tracks'], 'RMA tracks should match.');
    }

    public function testSearch()
    {
        $rma = $this->getRmaFixture();
        $rmaId = (int)$rma->getId();

        $request = [
            'searchCriteria' => [
                'filterGroups' => [
                    [
                        'filters' => [
                            [
                                'field' => Rma::ENTITY_ID,
                                'value' => $rmaId,
                                'conditionType' => 'eq',
                            ]
                        ],
                    ],
                ],
            ],
        ];
        $serviceInfo = [
            'rest' => [
                'resourcePath' => '/V1/returns' . '?' . http_build_query($request),
                'httpMethod' => Request::HTTP_METHOD_GET,
            ],
            'soap' => [
                'service' => self::SERVICE_NAME_SEARCH,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_NAME_SEARCH . 'search',
            ],
        ];

        $result = $this->_webApiCall($serviceInfo, $request);
        self::assertNotEmpty($result['items'][0]);

        $rma = $result['items'][0];
        self::assertEquals($rmaId, $rma[Rma::ENTITY_ID]);
        $this->performAsserts($this->rmaItems, $rma['items'], 'Rma Items are not correct');

        $rmaComments = $this->getRmaComments($rmaId);
        $this->performAsserts($rmaComments['items'], $rma['comments'], 'Rma Comments are not correct');

        $rmaTracks = $this->getRmaTracks($rmaId);
        self::assertNotEmpty($rma['tracks']);
        $this->performAsserts($rmaTracks['items'], $rma['tracks'], 'RMA tracks should match.');
    }

    /**
     * Return last created Rma fixture
     *
     * @return \Magento\Rma\Model\Rma
     */
    private function getRmaFixture()
    {
        $collection = $this->objectManager->create(\Magento\Rma\Model\ResourceModel\Rma\Collection::class);
        $collection->setOrder('entity_id')
            ->setPageSize(1)
            ->load();
        return $collection->fetchItem();
    }

    /**
     * Get comments of RMA by entity id
     *
     * @param int $rmaEntityId
     * @return array
     */
    private function getRmaComments(int $rmaEntityId): array
    {
        $serviceInfo = [
            'rest' => [
                'resourcePath' => '/V1/returns/' . $rmaEntityId . '/comments',
                'httpMethod' => Request::HTTP_METHOD_GET,
            ],
            'soap' => [
                'service' => 'rmaCommentManagementV1',
                'serviceVersion' => 'V1',
                'operation' => 'rmaCommentManagementV1commentsList',
            ],
        ];
        return $this->_webApiCall($serviceInfo, ['id' => $rmaEntityId]);
    }

    /**
     * Gets list of RMA tracking numbers.
     *
     * @param int $rmaEntityId
     * @return array
     */
    private function getRmaTracks(int $rmaEntityId): array
    {
        $serviceInfo = [
            'rest' => [
                'resourcePath' => '/V1/returns/' . $rmaEntityId . '/tracking-numbers',
                'httpMethod' => Request::HTTP_METHOD_GET,
            ],
            'soap' => [
                'service' => 'rmaTrackManagementV1',
                'serviceVersion' => 'V1',
                'operation' => 'rmaTrackManagementV1getTracks',
            ],
        ];
        return $this->_webApiCall($serviceInfo, ['id' => $rmaEntityId]);
    }

    /**
     * Compare rma related items
     *
     * @param array $rmaItems
     * @param array $resultItems
     * @return bool
     */
    private function compareItems(array $rmaItems, array $resultItems): bool
    {
        $result = true;

        foreach ($resultItems as $key => $resultItem) {
            $rmaItemData = $rmaItems[$key];
            $fieldsToCompare = array_intersect_key($resultItem, $rmaItemData);
            foreach ($fieldsToCompare as $fieldName => $fieldValue) {
                if ((string)$rmaItemData[$fieldName] !== (string)$fieldValue) {
                    $result = false;
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Performs test assertions.
     *
     * @param array $expected
     * @param array $actual
     * @param string $message
     */
    private function performAsserts(array $expected, array $actual, string $message)
    {
        self::assertTrue(
            $this->compareItems($expected, $actual),
            $message
        );
    }
}
