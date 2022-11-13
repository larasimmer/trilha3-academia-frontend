<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Model;

use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Checkout\Api\GuestShippingInformationManagementInterface;
use Magento\Checkout\Model\Cart\ImageProvider;
use Magento\Checkout\Model\DefaultConfigProvider;
use Magento\Customer\Model\AttributeFactory;
use Magento\Customer\Model\Context as CustomerContext;
use Magento\Customer\Model\ResourceModel\Grid\Collection as GridCollection;
use Magento\Customer\Model\Session;
use Magento\CustomerCustomAttributes\Model\Sales\Quote\Address as CustomAttributesQuoteAddressModel;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterfaceFactory;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface;
use Magento\TestFramework\ObjectManager;

/**
 * @magentoAppArea frontend
 */
class AddressAttributeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DefaultConfigProvider
     */
    private $checkoutConfigProvider;

    /**
     * @var GuestShippingInformationManagementInterface
     */
    private $guestShippingInformationManagement;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteria;

    /**
     * @var QuoteIdToMaskedQuoteIdInterface
     */
    private $quoteToMask;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

        $imageProvider = $this->getMockBuilder(ImageProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectManager->addSharedInstance($imageProvider, ImageProvider::class);

        $this->checkoutConfigProvider = $this->objectManager->create(
            DefaultConfigProvider::class
        );
        $this->guestShippingInformationManagement = $this->objectManager->get(
            GuestShippingInformationManagementInterface::class
        );
        $this->cartRepository = $this->objectManager->get(CartRepositoryInterface::class);
        $this->searchCriteria = $this->objectManager->get(SearchCriteriaBuilder::class);
        $this->quoteToMask = $this->objectManager->get(QuoteIdToMaskedQuoteIdInterface::class);
    }

    /**
     * Tests file type attribute
     *
     * @return void
     *
     * @magentoAppArea webapi_rest
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoDataFixture Magento/CustomerCustomAttributes/_files/address_custom_file_attribute.php
     */
    public function testFileAttribute(): void
    {
        $file = '/f/1/file.jpg';

        $addressData = [
            'country_id' => 'GB',
            'region' => '',
            'street' => '221b, Baker street',
            'company' => '',
            'telephone' => '+380959595995',
            'postcode' => '88000',
            'city' => 'London',
            'firstname' => 'John',
            'lastname' => 'Doe',
            'custom_attributes' => [
                [
                    'attribute_code' => 'document',
                    'value' => [
                        'value' => [
                            [
                                'file' => $file,
                                'size' => 242442,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $cart = $this->getCartByReservedOrderId('test01');

        $apiAddressFactory = $this->objectManager->create(AddressInterfaceFactory::class);
        $billingAddress = $apiAddressFactory->create(['data' => $addressData]);
        $shippingAddress = $apiAddressFactory->create(['data' => $addressData]);

        $shippingInformation = $this->objectManager->create(ShippingInformationInterface::class);
        $shippingInformation->setBillingAddress($billingAddress)
            ->setShippingAddress($shippingAddress)
            ->setShippingCarrierCode('flatrate')
            ->setShippingMethodCode('flatrate');

        $this->guestShippingInformationManagement->saveAddressInformation(
            $this->quoteToMask->execute((int)$cart->getId()),
            $shippingInformation
        );

        $cartAddresses = $cart->getAddressesCollection();

        foreach ($cartAddresses as $address) {
            /** @var CustomAttributesQuoteAddressModel $quoteAddress */
            $customAttributesAddressModel = $this->objectManager->create(CustomAttributesQuoteAddressModel::class);
            $customAttributesAddressModel->load($address->getAddressId());

            $this->assertEquals($file, $customAttributesAddressModel->getDocument());
        }
    }

    /**
     * Tests select, multiselect, text are properly modified on save in database
     *
     * @magentoAppArea webapi_rest
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoDataFixture Magento/CustomerCustomAttributes/_files/address_custom_attributes_without_transaction_select.php
     * @magentoDbIsolation disabled
     */
    public function testTextSelectMultiselectAttributes(): void
    {
        /** @var AttributeInterface $attribute */
        $attribute = $this->objectManager->create(AttributeFactory::class)->create();
        $select = $attribute->loadByCode('customer_address', 'select_code');
        $selectOptions = $select->getSource()->getAllOptions(false);
        $multiselect = $attribute->loadByCode('customer_address', 'multi_select_code');
        $multiselectOptions = $multiselect->getSource()->getAllOptions(false);
        $multiselectOptionValue = reset($multiselectOptions)['value'];
        $selectOptionValue = reset($selectOptions)['value'];
        $textOptionValue = 'test text';
        $addressData = [
            'country_id' => 'US',
            'region' => '',
            'street' => '221b, Baker street',
            'company' => '',
            'telephone' => '+12345678',
            'postcode' => '90230',
            'city' => 'Culver City',
            'firstname' => 'John',
            'lastname' => 'Doe',
            'custom_attributes' => [
                [
                    'attribute_code' => 'text_code',
                    'value' => [
                        'attribute_code' => 'test_text_code',
                        'value' => $textOptionValue,
                    ],
                ],
                [
                    'attribute_code' => 'multi_select_code',
                    'value' => [
                        'attribute_code' => 'multi_select_attribute_code',
                        'value' => $multiselectOptionValue,
                    ],
                ],
                [
                    'attribute_code' => 'select_code',
                    'value' => [
                        'attribute_code' => 'test_select_code',
                        'value' => $selectOptionValue,
                    ],
                ],
            ],
        ];

        $cart = $this->getCartByReservedOrderId('test01');
        $apiAddressFactory = $this->objectManager->create(AddressInterfaceFactory::class);

        $shippingInformation = $this->objectManager->create(ShippingInformationInterface::class);
        $shippingInformation->setBillingAddress($apiAddressFactory->create(['data' => $addressData]))
            ->setShippingAddress($apiAddressFactory->create(['data' => $addressData]))
            ->setShippingCarrierCode('flatrate')
            ->setShippingMethodCode('flatrate');

        $this->guestShippingInformationManagement->saveAddressInformation(
            $this->quoteToMask->execute((int)$cart->getId()),
            $shippingInformation
        );

        $cartAddresses = $this->getCartByReservedOrderId('test01')->getAddressesCollection();
        foreach ($cartAddresses as $address) {
            $customAttributes = $address->getCustomAttributes();
            $expectedValues = [
                'multi_select_code' => $multiselectOptionValue,
                'select_code' => $selectOptionValue,
                'text_code' => $textOptionValue,
            ];
            $this->assertGreaterThanOrEqual(3, $customAttributes);
            foreach ($customAttributes as $attribute) {
                $attributeCode = $attribute->getAttributeCode();
                if (array_key_exists($attributeCode, $expectedValues)) {
                    $this->assertEquals($expectedValues[$attributeCode], $attribute->getValue());
                }
            }
        }
    }

    /**
     * Tests that custom address attributes with 'is_visible' option 0 are filtered
     * from checkout config provider and not visible on Storefront.
     *
     * @magentoDataFixture Magento/Sales/_files/quote_with_customer.php
     * @magentoDataFixture Magento/CustomerCustomAttributes/_files/customer_with_address_custom_attributes.php
     */
    public function testVisibilityOnStorefront()
    {
        $customerId = 1;

        /** @var Session $customerSession */
        $customerSession = $this->objectManager->get(Session::class);
        $customerSession->setCustomerId($customerId);

        /** @var HttpContext $httpContext */
        $httpContext = $this->objectManager->get(HttpContext::class);
        $httpContext->setValue(CustomerContext::CONTEXT_AUTH, 1, 1);

        $data = $this->checkoutConfigProvider->getConfig();

        $this->performAssertions($data['customerData']['addresses']);
    }

    /**
     * Tests that Custom Customer Address Attribute will appear in Customer Grid.
     *
     * @magentoAppArea adminhtml
     * @magentoDataFixture Magento/CustomerCustomAttributes/_files/customer_with_address_custom_attribute_in_grid.php
     * @magentoDbIsolation disabled
     */
    public function testVisibilityOnGrid()
    {
        /** @var GridCollection $gridCustomerCollection */
        $gridCustomerCollection = $this->objectManager->create(GridCollection::class);
        /** @var \Magento\Customer\Ui\Component\DataProvider\Document $item */
        $item = $gridCustomerCollection->getItemByColumnValue('email', 'addressattribute@visibilityongrid.com');
        $this->assertEquals('123q', $item->getData('billing_customer_code'));
    }

    /**
     * @param array $addresses
     * @return void
     */
    private function performAssertions(array $addresses)
    {
        foreach ($addresses as $address) {
            $this->assertArrayHasKey('custom_attributes', $address);
            $this->assertEmpty($address['custom_attributes']);
        }
    }

    /**
     * @param $reservedOrderId
     * @return CartInterface
     */
    private function getCartByReservedOrderId($reservedOrderId): CartInterface
    {
        $this->searchCriteria = $this->objectManager->create(SearchCriteriaBuilder::class);
        $searchCriteria = $this->searchCriteria->addFilter(
            'reserved_order_id',
            $reservedOrderId
        )->create();
        $carts = $this->cartRepository->getList($searchCriteria)
            ->getItems();

        return reset($carts);
    }
}
