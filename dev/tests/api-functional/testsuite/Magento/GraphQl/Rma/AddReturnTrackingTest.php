<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\GraphQl\Rma;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\ObjectManagerInterface;
use Magento\GraphQl\GetCustomerAuthenticationHeader;
use Magento\Rma\Api\Data\RmaInterface;
use Magento\Rma\Api\RmaRepositoryInterface;
use Magento\Rma\Model\Rma;
use Magento\RmaGraphQl\Helper\Data;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test for Adding return tracking
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AddReturnTrackingTest extends GraphQlAbstract
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var GetCustomerAuthenticationHeader
     */
    private $getCustomerAuthenticationHeader;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var RmaRepositoryInterface
     */
    private $rmaRepository;

    /**
     * @var Uid
     */
    private $idEncoder;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Setup
     */
    public function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->getCustomerAuthenticationHeader = $this->objectManager->get(GetCustomerAuthenticationHeader::class);
        $this->customerRepository = $this->objectManager->get(CustomerRepositoryInterface::class);
        $this->searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        $this->rmaRepository = $this->objectManager->get(RmaRepositoryInterface::class);
        $this->idEncoder = $this->objectManager->get(Uid::class);
        $this->helper = $this->objectManager->get(Data::class);
        $this->storeManager = $this->objectManager->get(StoreManagerInterface::class);
    }

    /**
     * Test tracking adding by unauthorized customer
     *
     * @magentoConfigFixture sales/magento_rma/enabled 1
     * @magentoConfigFixture sales/magento_rma/use_store_address 1
     * @magentoConfigFixture shipping/origin/name test
     * @magentoConfigFixture shipping/origin/phone +380003434343
     * @magentoConfigFixture shipping/origin/street_line1 street
     * @magentoConfigFixture shipping/origin/street_line2 1
     * @magentoConfigFixture shipping/origin/city Montgomery
     * @magentoConfigFixture shipping/origin/region_id 1
     * @magentoConfigFixture shipping/origin/postcode 12345
     * @magentoConfigFixture shipping/origin/country_id US
     * @magentoConfigFixture carriers/dhl/active_rma 1
     * @magentoConfigFixture carriers/usps/active_rma 1
     * @magentoConfigFixture carriers/ups/active_rma 1
     * @magentoConfigFixture carriers/fedex/active_rma 1
     * @magentoApiDataFixture Magento/Rma/_files/rma_with_customer.php
     */
    public function testUnauthorized()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The current customer isn\'t authorized.');

        $rma = $this->getCustomerReturn('customer_uk_address@test.com');
        $rmaUid = $this->idEncoder->encode((string)$rma->getEntityId());

        $storeId = (int)$this->storeManager->getStore()->getId();
        $carrierUid = $this->helper->encodeCarrierId('ups', $storeId);

        $mutation = <<<MUTATION
mutation {
  addReturnTracking(
    input: {
      return_uid: "{$rmaUid}",
      carrier_uid: "{$carrierUid}",
      tracking_number: "4234213"
    }
  ) {
    return {
      shipping {
        tracking {
          uid
          carrier {
            label
          }
          tracking_number
        }
      }
    }
  }
}
MUTATION;

        $this->graphQlMutation($mutation);
    }

    /**
     * Test tracking adding when RMA is disabled
     *
     * @magentoApiDataFixture Magento/Rma/_files/rma_with_customer.php
     * @magentoConfigFixture sales/magento_rma/use_store_address 1
     * @magentoConfigFixture shipping/origin/name test
     * @magentoConfigFixture shipping/origin/phone +380003434343
     * @magentoConfigFixture shipping/origin/street_line1 street
     * @magentoConfigFixture shipping/origin/street_line2 1
     * @magentoConfigFixture shipping/origin/city Montgomery
     * @magentoConfigFixture shipping/origin/region_id 1
     * @magentoConfigFixture shipping/origin/postcode 12345
     * @magentoConfigFixture shipping/origin/country_id US
     * @magentoConfigFixture sales/magento_rma/enabled 0
     * @magentoConfigFixture carriers/dhl/active_rma 1
     * @magentoConfigFixture carriers/usps/active_rma 1
     * @magentoConfigFixture carriers/ups/active_rma 1
     * @magentoConfigFixture carriers/fedex/active_rma 1
     */
    public function testWithDisabledRma()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('RMA is disabled.');

        $customerEmail = 'customer_uk_address@test.com';
        $rma = $this->getCustomerReturn($customerEmail);
        $rmaUid = $this->idEncoder->encode((string)$rma->getEntityId());

        $storeId = (int)$this->storeManager->getStore()->getId();
        $carrierUid = $this->helper->encodeCarrierId('ups', $storeId);

        $mutation = <<<MUTATION
mutation {
  addReturnTracking(
    input: {
      return_uid: "{$rmaUid}",
      carrier_uid: "{$carrierUid}",
      tracking_number: "4234213"
    }
  ) {
    return {
      shipping {
        tracking {
          uid
          carrier {
            label
          }
          tracking_number
        }
      }
    }
  }
}
MUTATION;

        $this->graphQlMutation(
            $mutation,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute($customerEmail, 'password')
        );
    }

    /**
     * Test tracking adding
     *
     * @magentoConfigFixture sales/magento_rma/enabled 1
     * @magentoConfigFixture sales/magento_rma/use_store_address 1
     * @magentoConfigFixture shipping/origin/name test
     * @magentoConfigFixture shipping/origin/phone +380003434343
     * @magentoConfigFixture shipping/origin/street_line1 street
     * @magentoConfigFixture shipping/origin/street_line2 1
     * @magentoConfigFixture shipping/origin/city Montgomery
     * @magentoConfigFixture shipping/origin/region_id 1
     * @magentoConfigFixture shipping/origin/postcode 12345
     * @magentoConfigFixture shipping/origin/country_id US
     * @magentoConfigFixture carriers/dhl/active_rma 1
     * @magentoConfigFixture carriers/usps/active_rma 1
     * @magentoConfigFixture carriers/ups/active_rma 1
     * @magentoConfigFixture carriers/fedex/active_rma 1
     * @magentoApiDataFixture Magento/Rma/_files/rma_with_customer.php
     */
    public function testAddReturnTracking()
    {
        $customerEmail = 'customer_uk_address@test.com';
        $rma = $this->getCustomerReturn($customerEmail);
        $rmaUid = $this->idEncoder->encode((string)$rma->getEntityId());

        $storeId = (int)$this->storeManager->getStore()->getId();
        $carrierUid = $this->helper->encodeCarrierId('ups', $storeId);

        $trackingNumber = '4234213';

        $mutation = <<<MUTATION
mutation {
  addReturnTracking(
    input: {
      return_uid: "{$rmaUid}",
      carrier_uid: "{$carrierUid}",
      tracking_number: "{$trackingNumber}"
    }
  ) {
    return {
      shipping {
        tracking {
          uid
          carrier {
            uid
            label
          }
          tracking_number
        }
      }
    }
  }
}
MUTATION;

        $response = $this->graphQlMutation(
            $mutation,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute($customerEmail, 'password')
        );

        self::assertEquals(
            'TrackNumber',
            $response['addReturnTracking']['return']['shipping']['tracking'][0]['tracking_number']
        );
        self::assertEquals(
            $this->helper->encodeCarrierId('custom', $storeId),
            $response['addReturnTracking']['return']['shipping']['tracking'][0]['carrier']['uid']
        );
        self::assertEquals(
            'CarrierTitle',
            $response['addReturnTracking']['return']['shipping']['tracking'][0]['carrier']['label']
        );
        self::assertEquals(
            $carrierUid,
            $response['addReturnTracking']['return']['shipping']['tracking'][1]['carrier']['uid']
        );
        self::assertEquals(
            $trackingNumber,
            $response['addReturnTracking']['return']['shipping']['tracking'][1]['tracking_number']
        );
        self::assertEquals(
            'United Parcel Service',
            $response['addReturnTracking']['return']['shipping']['tracking'][1]['carrier']['label']
        );
    }

    /**
     * Test tracking adding with wrong RMA id
     *
     * @magentoConfigFixture sales/magento_rma/enabled 1
     * @magentoConfigFixture sales/magento_rma/use_store_address 1
     * @magentoConfigFixture shipping/origin/name test
     * @magentoConfigFixture shipping/origin/phone +380003434343
     * @magentoConfigFixture shipping/origin/street_line1 street
     * @magentoConfigFixture shipping/origin/street_line2 1
     * @magentoConfigFixture shipping/origin/city Montgomery
     * @magentoConfigFixture shipping/origin/region_id 1
     * @magentoConfigFixture shipping/origin/postcode 12345
     * @magentoConfigFixture shipping/origin/country_id US
     * @magentoConfigFixture carriers/dhl/active_rma 1
     * @magentoConfigFixture carriers/usps/active_rma 1
     * @magentoConfigFixture carriers/ups/active_rma 1
     * @magentoConfigFixture carriers/fedex/active_rma 1
     * @magentoApiDataFixture Magento/Rma/_files/rma_with_customer.php
     */
    public function testWithWrongRmaId()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('You selected the wrong RMA.');

        $customerEmail = 'customer_uk_address@test.com';
        $rma = $this->getCustomerReturn($customerEmail);
        $rmaId = $rma->getEntityId() + 10;
        $rmaUid = $this->idEncoder->encode((string)$rmaId);

        $storeId = (int)$this->storeManager->getStore()->getId();
        $carrierUid = $this->helper->encodeCarrierId('ups', $storeId);

        $mutation = <<<MUTATION
mutation {
  addReturnTracking(
    input: {
      return_uid: "{$rmaUid}",
      carrier_uid: "{$carrierUid}",
      tracking_number: "4234213"
    }
  ) {
    return {
      shipping {
        tracking {
          uid
          carrier {
            label
          }
          tracking_number
        }
      }
    }
  }
}
MUTATION;

        $this->graphQlMutation(
            $mutation,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute($customerEmail, 'password')
        );
    }

    /**
     * Test tracking adding with not encoded RMA id
     *
     * @magentoConfigFixture sales/magento_rma/enabled 1
     * @magentoConfigFixture sales/magento_rma/use_store_address 1
     * @magentoConfigFixture shipping/origin/name test
     * @magentoConfigFixture shipping/origin/phone +380003434343
     * @magentoConfigFixture shipping/origin/street_line1 street
     * @magentoConfigFixture shipping/origin/street_line2 1
     * @magentoConfigFixture shipping/origin/city Montgomery
     * @magentoConfigFixture shipping/origin/region_id 1
     * @magentoConfigFixture shipping/origin/postcode 12345
     * @magentoConfigFixture shipping/origin/country_id US
     * @magentoConfigFixture carriers/dhl/active_rma 1
     * @magentoConfigFixture carriers/usps/active_rma 1
     * @magentoConfigFixture carriers/ups/active_rma 1
     * @magentoConfigFixture carriers/fedex/active_rma 1
     * @magentoApiDataFixture Magento/Rma/_files/rma_with_customer.php
     */
    public function testWithNotEncodedRmaId()
    {
        $customerEmail = 'customer_uk_address@test.com';
        $rma = $this->getCustomerReturn($customerEmail);
        $rmaId = $rma->getEntityId();

        $storeId = (int)$this->storeManager->getStore()->getId();
        $carrierUid = $this->helper->encodeCarrierId('ups', $storeId);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Value of uid \"{$rmaId}\" is incorrect");

        $mutation = <<<MUTATION
mutation {
  addReturnTracking(
    input: {
      return_uid: "{$rmaId}",
      carrier_uid: "{$carrierUid}",
      tracking_number: "4234213"
    }
  ) {
    return {
      shipping {
        tracking {
          uid
          carrier {
            label
          }
          tracking_number
        }
      }
    }
  }
}
MUTATION;

        $this->graphQlMutation(
            $mutation,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute($customerEmail, 'password')
        );
    }

    /**
     * Test tracking adding with wrong carrier id
     *
     * @magentoConfigFixture sales/magento_rma/enabled 1
     * @magentoConfigFixture sales/magento_rma/use_store_address 1
     * @magentoConfigFixture shipping/origin/name test
     * @magentoConfigFixture shipping/origin/phone +380003434343
     * @magentoConfigFixture shipping/origin/street_line1 street
     * @magentoConfigFixture shipping/origin/street_line2 1
     * @magentoConfigFixture shipping/origin/city Montgomery
     * @magentoConfigFixture shipping/origin/region_id 1
     * @magentoConfigFixture shipping/origin/postcode 12345
     * @magentoConfigFixture shipping/origin/country_id US
     * @magentoConfigFixture carriers/dhl/active_rma 1
     * @magentoConfigFixture carriers/usps/active_rma 1
     * @magentoConfigFixture carriers/ups/active_rma 1
     * @magentoConfigFixture carriers/fedex/active_rma 1
     * @magentoApiDataFixture Magento/Rma/_files/rma_with_customer.php
     */
    public function testWithWrongCarrierId()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Please select a valid carrier.');

        $customerEmail = 'customer_uk_address@test.com';
        $rma = $this->getCustomerReturn($customerEmail);
        $rmaUid = $this->idEncoder->encode((string)$rma->getEntityId());

        $storeId = (int)$this->storeManager->getStore()->getId();
        $carrierUid = $this->helper->encodeCarrierId('notExistingCarrier', $storeId);

        $mutation = <<<MUTATION
mutation {
  addReturnTracking(
    input: {
      return_uid: "{$rmaUid}",
      carrier_uid: "{$carrierUid}",
      tracking_number: "4234213"
    }
  ) {
    return {
      shipping {
        tracking {
          uid
          carrier {
            label
          }
          tracking_number
        }
      }
    }
  }
}
MUTATION;

        $this->graphQlMutation(
            $mutation,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute($customerEmail, 'password')
        );
    }

    /**
     * Test tracking adding with wrong carrier store id
     *
     * @magentoConfigFixture sales/magento_rma/enabled 1
     * @magentoConfigFixture sales/magento_rma/use_store_address 1
     * @magentoConfigFixture shipping/origin/name test
     * @magentoConfigFixture shipping/origin/phone +380003434343
     * @magentoConfigFixture shipping/origin/street_line1 street
     * @magentoConfigFixture shipping/origin/street_line2 1
     * @magentoConfigFixture shipping/origin/city Montgomery
     * @magentoConfigFixture shipping/origin/region_id 1
     * @magentoConfigFixture shipping/origin/postcode 12345
     * @magentoConfigFixture shipping/origin/country_id US
     * @magentoConfigFixture carriers/dhl/active_rma 1
     * @magentoConfigFixture carriers/usps/active_rma 1
     * @magentoConfigFixture carriers/ups/active_rma 1
     * @magentoConfigFixture carriers/fedex/active_rma 1
     * @magentoApiDataFixture Magento/Rma/_files/rma_with_customer.php
     */
    public function testWithWrongCarrierStoreId()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Please select a valid carrier.');

        $customerEmail = 'customer_uk_address@test.com';
        $rma = $this->getCustomerReturn($customerEmail);
        $rmaUid = $this->idEncoder->encode((string)$rma->getEntityId());

        $storeId = (int)$this->storeManager->getStore()->getId() + 100;
        $carrierUid = $this->helper->encodeCarrierId('ups', $storeId);

        $mutation = <<<MUTATION
mutation {
  addReturnTracking(
    input: {
      return_uid: "{$rmaUid}",
      carrier_uid: "{$carrierUid}",
      tracking_number: "4234213"
    }
  ) {
    return {
      shipping {
        tracking {
          uid
          carrier {
            label
          }
          tracking_number
        }
      }
    }
  }
}
MUTATION;

        $this->graphQlMutation(
            $mutation,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute($customerEmail, 'password')
        );
    }

    /**
     * Test tracking adding with not encoded carrier id
     *
     * @magentoConfigFixture sales/magento_rma/enabled 1
     * @magentoConfigFixture sales/magento_rma/use_store_address 1
     * @magentoConfigFixture shipping/origin/name test
     * @magentoConfigFixture shipping/origin/phone +380003434343
     * @magentoConfigFixture shipping/origin/street_line1 street
     * @magentoConfigFixture shipping/origin/street_line2 1
     * @magentoConfigFixture shipping/origin/city Montgomery
     * @magentoConfigFixture shipping/origin/region_id 1
     * @magentoConfigFixture shipping/origin/postcode 12345
     * @magentoConfigFixture shipping/origin/country_id US
     * @magentoConfigFixture carriers/dhl/active_rma 1
     * @magentoConfigFixture carriers/usps/active_rma 1
     * @magentoConfigFixture carriers/ups/active_rma 1
     * @magentoConfigFixture carriers/fedex/active_rma 1
     * @magentoApiDataFixture Magento/Rma/_files/rma_with_customer.php
     */
    public function testWithNotEncodedCarrierId()
    {
        $customerEmail = 'customer_uk_address@test.com';
        $rma = $this->getCustomerReturn($customerEmail);
        $rmaUid = $this->idEncoder->encode((string)$rma->getEntityId());

        $storeId = (int)$this->storeManager->getStore()->getId();
        $carrierId = 'ups-' . $storeId;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Please select a valid carrier.');

        $mutation = <<<MUTATION
mutation {
  addReturnTracking(
    input: {
      return_uid: "{$rmaUid}",
      carrier_uid: "{$carrierId}",
      tracking_number: "4234213"
    }
  ) {
    return {
      shipping {
        tracking {
          uid
          carrier {
            label
          }
          tracking_number
        }
      }
    }
  }
}
MUTATION;

        $this->graphQlMutation(
            $mutation,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute($customerEmail, 'password')
        );
    }

    /**
     * Get customer return
     *
     * @param string $customerEmail
     * @return RmaInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getCustomerReturn(string $customerEmail): RmaInterface
    {
        $customer = $this->customerRepository->get($customerEmail);
        $this->searchCriteriaBuilder->addFilter(Rma::CUSTOMER_ID, $customer->getId());
        $searchResults = $this->rmaRepository->getList($this->searchCriteriaBuilder->create());

        return $searchResults->getFirstItem();
    }
}
