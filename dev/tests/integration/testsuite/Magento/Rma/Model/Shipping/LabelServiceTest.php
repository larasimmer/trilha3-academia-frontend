<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Model\Shipping;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Webapi\Soap\ClientFactory;
use Magento\Rma\Api\Data\TrackInterface;
use Magento\Rma\Api\RmaRepositoryInterface;
use Magento\Rma\Api\TrackRepositoryInterface;
use Magento\Rma\Model\Rma;
use Magento\Rma\Model\Shipping;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject as MockObject;

/**
 * @magentoAppArea adminhtml
 */
class LabelServiceTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var LabelService
     */
    private $service;

    /**
     * @var ClientFactory|MockObject
     */
    private $clientFactory;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();

        $this->clientFactory = $this->getMockBuilder(ClientFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager->addSharedInstance($this->clientFactory, ClientFactory::class);

        $this->service = $this->objectManager->create(LabelService::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        $this->objectManager->removeSharedInstance(ClientFactory::class);
    }

    /**
     * Checks a case when carrier can return multiple tracking codes during shipping label generation.
     *
     * @magentoConfigFixture current_store carriers/fedex/active 1
     * @magentoConfigFixture current_store carriers/fedex/active_rma 1
     * @magentoConfigFixture current_store sales/magento_rma/use_store_address 0
     * @magentoConfigFixture current_store general/store_information/country_id US
     * @magentoConfigFixture current_store general/store_information/phone 321789821
     * @magentoConfigFixture current_store general/store_information/name Company
     * @magentoConfigFixture current_store sales/magento_rma/city Los Angeles
     * @magentoConfigFixture current_store sales/magento_rma/address1 Street 1
     * @magentoConfigFixture current_store sales/magento_rma/zip 11111
     * @magentoConfigFixture current_store sales/magento_rma/region_id 12
     * @magentoConfigFixture current_store sales/magento_rma/country_id US
     * @magentoConfigFixture current_store sales/magento_rma/store_name Default
     * @magentoDataFixture Magento/Rma/_files/rma.php
     */
    public function testCreateShippingLabel()
    {
        $expNumbers = ['61211111114921000401', '800029216568212'];
        $data = [
            'code' => 'fedex_SMART_POST',
            'carrier_title' => 'Federal Express',
            'method_title' => 'Smart Post',
            'price' => '9.04',
            'packages' => [
                [
                    'params' => [
                        'container' => 'YOUR_PACKAGING',
                        'weight' => '1',
                        'customs_value' => '9.99',
                        'length' => '20',
                        'width' => '20',
                        'height' => '20',
                        'weight_units' => 'POUND',
                        'dimension_units' => 'INCH',
                        'delivery_confirmation' => 'NO_SIGNATURE_REQUIRED',
                    ],
                    'items' => [
                        [
                            'qty' => '1',
                            'customs_value' => '9.99',
                            'price' => '9.9900',
                            'name' => 'Simple Product 1',
                            'weight' => '1.0000',
                            'product_id' => '1',
                            'order_item_id' => '16',
                        ],
                    ],
                ],
            ],
        ];

        /** @var \SoapClient|MockObject $client */
        $client = $this->getMockBuilder(\SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['processShipment'])
            ->getMock();
        $this->clientFactory->method('create')
            ->willReturn($client);

        $client->method('processShipment')
            ->with(self::callback(function ($request) {
                self::assertEquals(
                    'SENDER',
                    $request['RequestedShipment']['ShippingChargesPayment']['PaymentType'],
                    'Payment Type for the Return request via Fedex Smart Post should be SENDER.'
                );
                return true;
            }))
            ->willReturn($this->getXmlResponseData(__DIR__ . '/../../Fixtures/LabelResponse.xml'));

        $rmaModel = $this->getRma('1');
        self::assertTrue($this->service->createShippingLabel($rmaModel, $data));

        $rmaTracks = $this->getRmaTracks((int)$rmaModel->getEntityId());
        self::assertCount(2, $rmaTracks);

        $actualNumbers = [];
        /** @var TrackInterface $track */
        foreach ($rmaTracks as $track) {
            $actualNumbers[] = $track->getTrackNumber();
        }
        self::assertEquals($expNumbers, $actualNumbers);
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

    /**
     * Gets list of RMA items.
     *
     * @param int $rmaId
     * @return array
     */
    private function getRmaTracks(int $rmaId): array
    {
        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        $searchCriteria = $searchCriteriaBuilder->addFilter('rma_entity_id', $rmaId)
            ->addFilter('is_admin', Shipping::IS_ADMIN_STATUS_ADMIN_LABEL_TRACKING_NUMBER)
            ->create();

        /** @var TrackRepositoryInterface $repository */
        $repository = $this->objectManager->get(TrackRepositoryInterface::class);
        return $repository->getList($searchCriteria)
            ->getItems();
    }

    /**
     * Gets XML document by provided path.
     *
     * @param string $filePath
     * @return \stdClass
     */
    private function getXmlResponseData(string $filePath): \stdClass
    {
        $data = file_get_contents($filePath);
        $xml = new \SimpleXMLElement($data);

        $data = json_decode(json_encode($xml));
        $data->CompletedShipmentDetail->CompletedPackageDetails->Label->Parts->Image =
            base64_decode('R0lGODlhAQABAIAAAAUEBAAAACwAAAAAAQABAAACAkQBADs=');
        return $data;
    }
}
