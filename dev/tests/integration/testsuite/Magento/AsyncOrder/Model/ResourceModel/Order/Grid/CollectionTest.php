<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AsyncOrder\Model\ResourceModel\Order\Grid;

use Magento\AsyncOrder\Model\AsyncPaymentInformationGuestPublisher;
use Magento\AsyncOrder\Model\OrderManagement;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Api\Data\TotalsInformationInterface;
use Magento\Checkout\Api\TotalsInformationManagementInterface;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Data\Collection;
use Magento\Framework\ObjectManagerInterface;
use Magento\Quote\Api\CartItemRepositoryInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\PaymentMethodManagementInterface;
use Magento\Quote\Model\ShippingAddressManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CollectionTest extends TestCase
{
    /**
     * @var Collection[]
     */
    private $collections;

    /**
     * @var string
     */
    private $requestName = 'sales_order_grid_data_source';

    /**
     * @var CartManagementInterface
     */
    private $cartManagement;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var PaymentMethodManagementInterface
     */
    private $paymentMethodManagement;

    /**
     * @var ShippingAddressManagementInterface
     */
    private $shippingAddressManagement;

    /**
     * @var TotalsInformationManagementInterface
     */
    private $totalsInformationManagement;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var CustomerInterfaceFactory
     */
    private $customerFactory;

    /**
     * @var CartItemRepositoryInterface
     */
    private $cartItemRepository;

    /**
     * @var AccountManagementInterface
     */
    private $accountManagement;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->collections[$this->requestName] = $this->objectManager->create(
            \Magento\AsyncOrder\Model\ResourceModel\Order\Grid\Collection::class
        );
        $this->cartManagement = $this->objectManager->create(CartManagementInterface::class);
        $this->cartItemRepository = $this->objectManager->create(CartItemRepositoryInterface::class);
        $this->orderRepository = $this->objectManager->create(OrderRepositoryInterface::class);
        $this->paymentMethodManagement = $this->objectManager->create(PaymentMethodManagementInterface::class);
        $this->totalsInformationManagement = $this->objectManager->create(TotalsInformationManagementInterface::class);
        $this->shippingAddressManagement = $this->objectManager->create(ShippingAddressManagementInterface::class);
        $this->dataObjectHelper = $this->objectManager->create(DataObjectHelper::class);
        $this->customerFactory = $this->objectManager->get(CustomerInterfaceFactory::class);
        $this->accountManagement = $this->objectManager->get(AccountManagementInterface::class);
    }

    /**
     * @magentoDataFixture Magento/AsyncOrder/_files/async_mode.php
     * @magentoAppArea frontend
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoDbIsolation enabled
     */
    public function testReceivedOrdersNotInSalesOrdersGrid()
    {
        $guestEmail = 'guest@example.com';
        $carrierCode = 'flatrate';
        $shippingMethodCode = 'flatrate';
        $paymentMethod = 'checkmo';
        $product = $this->getProduct(1);
        $shippingAddress = $this->getShippingAddress();
        $payment = $this->getPayment($paymentMethod);

        //Create cart and add product to it
        $cartId = $this->cartManagement->createEmptyCart();
        $this->addProductToCart($product, $cartId);

        //Assign shipping address
        $this->shippingAddressManagement->assign($cartId, $shippingAddress);
        $shippingAddress = $this->shippingAddressManagement->get($cartId);

        //Calculate totals
        $totals = $this->getTotals($shippingAddress, $carrierCode, $shippingMethodCode);
        $this->totalsInformationManagement->calculate($cartId, $totals);

        //Set payment method
        $this->paymentMethodManagement->set($cartId, $payment);

        /** @var AsyncPaymentInformationGuestPublisher $asyncPaymentInformationGuestPublisher */
        $asyncPaymentInformationGuestPublisher = $this->objectManager->create(
            AsyncPaymentInformationGuestPublisher::class
        );

        $orderId = $asyncPaymentInformationGuestPublisher->savePaymentInformationAndPlaceOrder(
            $cartId,
            $guestEmail,
            $payment,
            $this->getBillingAddress()
        );

        $this->assertNotNull($orderId);

        $order = $this->orderRepository->get($orderId);
        $this->assertEquals(
            OrderManagement::STATUS_RECEIVED,
            $order->getStatus(),
            'The current order has the wrong status '
        );

        $customerData = $expectedCustomerData = [
            CustomerInterface::EMAIL => 'guest@example.com',
            CustomerInterface::STORE_ID => 1,
            CustomerInterface::FIRSTNAME => 'Tester',
            CustomerInterface::LASTNAME => 'McTest',
            CustomerInterface::GROUP_ID => 1,
        ];

        $newCustomerEntity = $this->populateCustomerEntity($customerData);
        $savedCustomer = $this->accountManagement->createAccount($newCustomerEntity, '_aPassword1');
        $this->assertNotNull($savedCustomer->getId());
        $this->assertCustomerData($savedCustomer, $expectedCustomerData);
        $this->assertEmpty($savedCustomer->getSuffix());

        $this->assertEquals(0, $this->collections[$this->requestName]->count());
        $this->assertInstanceOf(
            \Magento\AsyncOrder\Model\ResourceModel\Order\Grid\Collection::class,
            $this->collections[$this->requestName]
        );
        $this->assertEquals('sales_order_grid', $this->collections[$this->requestName]->getMainTable());
    }

    /**
     * @param int $productId
     * @return ProductInterface
     */
    private function getProduct($productId)
    {
        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $this->objectManager->create(ProductRepositoryInterface::class);
        $product = $productRepository->getById($productId);
        $product->setOptions(null);
        $productRepository->save($product);
        return $product;
    }

    /**
     * @return AddressInterface
     */
    private function getShippingAddress()
    {
        $shippingAddress = $this->objectManager->create(AddressInterface::class);
        $shippingAddress->setFirstname('MyFirstName');
        $shippingAddress->setLastname('MyLastName');
        $shippingAddress->setStreet('MyStreet');
        $shippingAddress->setCity('MyCity');
        $shippingAddress->setTelephone('1234567890');
        $shippingAddress->setPostcode('12345');
        $shippingAddress->setRegionId(12);
        $shippingAddress->setCountryId('US');
        $shippingAddress->setSameAsBilling(true);
        return $shippingAddress;
    }

    /**
     * @param AddressInterface $shippingAddress
     * @param string $carrierCode
     * @param string $methodCode
     * @return TotalsInformationInterface
     */
    private function getTotals($shippingAddress, $carrierCode, $methodCode)
    {
        /** @var TotalsInformationInterface $totals */
        $totals = $this->objectManager->create(TotalsInformationInterface::class);
        $totals->setAddress($shippingAddress);
        $totals->setShippingCarrierCode($carrierCode);
        $totals->setShippingMethodCode($methodCode);

        return $totals;
    }

    /**
     * @param $paymentMethod
     *
     * @return PaymentInterface
     */
    private function getPayment($paymentMethod)
    {
        $payment = $this->objectManager->create(PaymentInterface::class);
        $payment->setMethod($paymentMethod);

        return $payment;
    }

    /**
     * @param ProductInterface $product
     * @param string $cartId
     */
    private function addProductToCart($product, $cartId)
    {
        /** @var CartItemInterface $quoteItem */
        $quoteItem = $this->objectManager->create(CartItemInterface::class);
        $quoteItem->setQuoteId($cartId);
        $quoteItem->setProduct($product);
        $quoteItem->setQty(2);
        $this->cartItemRepository->save($quoteItem);
    }

    /**
     * Get billing address
     *
     * @return AddressInterface
     */
    private function getBillingAddress()
    {
        /** @var AddressInterface $billingAddress */
        $billingAddress = $this->objectManager->create(AddressInterface::class);
        $billingAddress->setFirstname('First');
        $billingAddress->setLastname('Last');
        $billingAddress->setStreet('Street');
        $billingAddress->setCity('City');
        $billingAddress->setTelephone('1234567890');
        $billingAddress->setPostcode('12345');
        $billingAddress->setRegionId(12);
        $billingAddress->setCountryId('US');
        return $billingAddress;
    }

    /**
     * Fill in customer entity using array of customer data and additional customer data.
     *
     * @param array $customerData
     * @param array $additionalCustomerData
     * @param CustomerInterface|null $customerEntity
     * @return CustomerInterface
     */
    private function populateCustomerEntity(
        array $customerData,
        array $additionalCustomerData = [],
        ?CustomerInterface $customerEntity = null
    ): CustomerInterface {
        $customerEntity = $customerEntity ?? $this->customerFactory->create();
        $customerData = array_merge(
            $customerData,
            $additionalCustomerData
        );
        $this->dataObjectHelper->populateWithArray(
            $customerEntity,
            $customerData,
            CustomerInterface::class
        );
        return $customerEntity;
    }

    /**
     * Check that customer parameters match expected values.
     *
     * @param CustomerInterface $customer
     * @param array $expectedData
     * return void
     */
    private function assertCustomerData(
        CustomerInterface $customer,
        array $expectedData
    ): void {
        $actualCustomerArray = $customer->__toArray();
        foreach ($expectedData as $key => $expectedValue) {
            $this->assertEquals(
                $expectedValue,
                $actualCustomerArray[$key],
                "Invalid expected value for $key field."
            );
        }
    }
}
